<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageStreamed;
use App\Models\Conversation;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory;


class AskController extends Controller
{
    public function __construct(private ChatService $chatService) {}

    public function index()
    {
        $conversations = auth()->user()->conversations()
            ->with(['messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }, 'customInstruction']) // Add customInstruction eager loading
            ->orderBy('updated_at', 'desc')
            ->get();

        $customInstructions = auth()->user()->customInstructions()
            ->orderBy('created_at', 'desc')
            ->get();


        return Inertia::render('Ask/Index', [
            'models' => $this->chatService->getModels(),
            'conversations' => $conversations,
            'customInstructions' => $customInstructions,
        ]);
    }

    public function ask(Conversation $conversation, Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            // 'model'   => 'nullable|string',
        ]);

        try {
            // 1. Sauvegarder le message de l'utilisateur
            $conversation->messages()->create([
                'content' => $request->input('message'),
                'role'    => 'user',
            ]);

            // 2. Nom du canal
            $channelName = "chat.{$conversation->id}";

            // 3. Récupérer historique
            $messages = $conversation->messages()
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn($msg) => [
                    'role'    => $msg->role,
                    'content' => $msg->content,
                ])
                ->toArray();

            // 4. Obtenir un flux depuis le ChatService
            $stream = (new ChatService())->streamConversation(
                messages: $messages,
                model: $conversation->model ?? $request->user()->last_used_model ?? ChatService::DEFAULT_MODEL,
            );

            // 5. Créer le message "assistant" dans la BD (vide pour l'instant)
            $assistantMessage = $conversation->messages()->create([
                'content' => '',
                'role'    => 'assistant',
            ]);

            // 6. Variables pour accumuler la réponse
            $fullResponse = '';
            $buffer = '';
            $lastBroadcastTime = microtime(true) * 1000; // ms

            // 7. Itération sur le flux
            foreach ($stream as $response) {
                $chunk = $response->choices[0]->delta->content ?? '';

                if ($chunk) {
                    $fullResponse .= $chunk;
                    $buffer .= $chunk;

                    // Broadcast seulement toutes les ~100ms
                    $currentTime = microtime(true) * 1000;
                    if ($currentTime - $lastBroadcastTime >= 100) {
                        broadcast(new ChatMessageStreamed(
                            channel: $channelName,
                            content: $buffer,
                            isComplete: false
                        ));

                        $buffer = '';
                        $lastBroadcastTime = $currentTime;
                    }
                }
            }

            // 8. Diffuser le buffer restant
            if (!empty($buffer)) {
                broadcast(new ChatMessageStreamed(
                    channel: $channelName,
                    content: $buffer,
                    isComplete: false
                ));
            }

            // 9. Mettre à jour la BD avec le texte complet
            $assistantMessage->update([
                'content' => $fullResponse
            ]);

            // 10. Émettre un dernier événement pour signaler la complétion
            broadcast(new ChatMessageStreamed(
                channel: $channelName,
                content: $fullResponse,
                isComplete: true
            ));

            return back();
        } catch (\Exception $e) {
            // Diffuser l’erreur
            if (isset($conversation)) {
                broadcast(new ChatMessageStreamed(
                    channel: "chat.{$conversation->id}",
                    content: "Erreur: " . $e->getMessage(),
                    isComplete: true,
                    error: true
                ));
            }

            return back()->with('error', 'Une erreur est survenue lors du traitement de votre demande.');
        }
    }
    // public function ask(Request $request)
    // {
    //     // dd($request->all());
    //     $validatedData = $request->validate([
    //         'conversation_id' => 'required|exists:conversations,id',
    //         'message' => 'nullable|string',
    //         'files' => 'nullable|array',
    //         'files.*' => 'file|max:5120'
    //     ]);

    //     $conversation = Conversation::with(['customInstruction', 'messages'])
    //         ->findOrFail($validatedData['conversation_id']);

    //     try {
    //         $content = '';

    //         if (!empty($validatedData['message'])) {
    //             $content .= $validatedData['message'];
    //         }

    //         if ($request->hasFile('files')) {
    //             foreach ($request->file('files') as $file) {
    //                 logger()->info('Processing file:', ['name' => $file->getClientOriginalName()]);
    //                 $fileContent = $this->extractTextFromFile($file);
    //                 if ($fileContent) {
    //                     $content .= "File Content: {$file->getClientOriginalName()}\n\n{$fileContent}\n\n";
    //                 }
    //                 logger()->info('File processed:', ['name' => $file->getClientOriginalName(), 'content' => $fileContent]);
    //             }
    //         }


    //         if (!empty($content)) {
    //             $conversation->messages()->create([
    //                 'content' => trim($content),
    //                 'role' => 'user',
    //             ]);

    //             $response = $this->chatService->sendMessage(
    //                 conversation: $conversation,
    //                 model: $conversation->model_id
    //             );

    //             if ($response) {
    //                 $conversation->messages()->create([
    //                     'content' => $response,
    //                     'role' => 'assistant',
    //                 ]);
    //             }
    //         }

    //         if ($conversation->messages()->count() <= 2) {
    //             $title = $this->chatService->makeTitle(
    //                 conversation: $conversation,
    //             );
    //             $conversation->update(['title' => $title]);
    //         }

    //         return back()->with('conversation', $conversation->fresh(['messages', 'customInstruction']));
    //     } catch (\Exception $e) {
    //         logger()->error('Error in ask method:', ['error' => $e->getMessage()]);
    //         return back()->with('error', 'An error occurred while processing your request.');
    //     }
    // }

    private function extractTextFromFile($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        logger()->info('Extracting content from file:', [
            'name' => $file->getClientOriginalName(),
            'extension' => $extension
        ]);

        try {
            // Handle PDF files
            if ($extension === 'pdf') {
                return $this->extractTextFromPdf($file);
            }

            // Handle DOCX files
            if ($extension === 'docx') {
                return $this->extractTextFromDocx($file);
            }

            // Handle all text-based files
            $textBasedExtensions = ['txt', 'js', 'vue', 'php', 'log', 'css', 'html', 'json'];
            if (in_array($extension, $textBasedExtensions)) {
                return file_get_contents($file->getRealPath());
            }

            logger()->warning('Unsupported file type:', ['extension' => $extension]);
            return 'Unsupported file type: ' . $file->getClientOriginalName();
        } catch (\Exception $e) {
            logger()->error('Error processing file:', [
                'name' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function extractTextFromPdf($file)
    {
        $pdfParser = new \Smalot\PdfParser\Parser();

        // Parse the PDF file
        $pdfText = $pdfParser->parseFile($file->getRealPath());

        // Return the extracted text
        return $pdfText->getText();
    }

    private function extractTextFromDocx($file)
    {
        // Implement DOCX extraction logic (e.g., using PHPWord)
        $phpWord = IOFactory::load($file);
        $content = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                    foreach ($element->getElements() as $textElement) {
                        if ($textElement instanceof \PhpOffice\PhpWord\Element\Text) {
                            $content .= $textElement->getText() . "\n";
                        }
                    }
                } elseif ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                    $content .= $element->getText() . "\n";
                }
            }
        }

        return $content;
    }
};
