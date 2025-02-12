<?php

namespace App\Http\Controllers;

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

    public function ask(Request $request)
    {
        $validatedData = $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'message' => 'nullable|string',
            'files' => 'nullable|array',
            'files.*' => 'file|max:5120'
        ]);

        $conversation = Conversation::with(['customInstruction', 'messages'])
            ->findOrFail($validatedData['conversation_id']);

        try {
            $content = '';

            if (!empty($validatedData['message'])) {
                $content .= $validatedData['message'];
            }

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    logger()->info('Processing file:', ['name' => $file->getClientOriginalName()]);
                    $fileContent = $this->extractTextFromFile($file);
                    if ($fileContent) {
                        $content .= "File Content: {$file->getClientOriginalName()}\n\n{$fileContent}\n\n";
                    }
                    logger()->info('File processed:', ['name' => $file->getClientOriginalName(), 'content' => $fileContent]);
                }
            }


            if (!empty($content)) {
                $conversation->messages()->create([
                    'content' => trim($content),
                    'role' => 'user',
                ]);

                $response = $this->chatService->sendMessage(
                    conversation: $conversation,
                    model: $conversation->model_id
                );

                if ($response) {
                    $conversation->messages()->create([
                        'content' => $response,
                        'role' => 'assistant',
                    ]);
                }
            }

            if ($conversation->messages()->count() <= 2) {
                $title = $this->chatService->makeTitle(
                    conversation: $conversation,
                );
                $conversation->update(['title' => $title]);
            }

            return back()->with('conversation', $conversation->fresh(['messages', 'customInstruction']));
        } catch (\Exception $e) {
            logger()->error('Error in ask method:', ['error' => $e->getMessage()]);
            return back()->with('error', 'An error occurred while processing your request.');
        }
    }

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
