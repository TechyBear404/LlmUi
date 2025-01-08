<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AskController extends Controller
{
    public function index()
    {
        $models = (new ChatService())->getModels();
        $selectedModel = ChatService::DEFAULT_MODEL;
        $conversations = Conversation::all();
        $currentConversation = Conversation::with('messages')->latest()->first();

        return Inertia::render('Ask/Index', [
            'models' => $models,
            'selectedModel' => $selectedModel,
            'conversations' => $conversations,
            'currentConversation' => $currentConversation,
        ]);
    }

    public function ask(Request $request)
    {
        $request->validate([
            'conversation_id' => 'nullable|exists:conversations,id',
            'message' => 'required|string',
            'model' => 'required|string',
        ]);

        try {
            $message = [
                'role' => 'user',
                'content' => $request->message,
            ];
            if ($request->conversation_id) {
                $conversation = Conversation::findOrFail($request->conversation_id);
            } else {
                $conversation = Conversation::create();
                $titleMessage = $message;
                $titleMessage['content'] = 'donne moi un titre de conversation en une ligne pour ce message, soit clair et concis pas plus de 10 mots : ' . $message['content'];

                $conversation->title = (new ChatService())->sendMessage(
                    messages: [$titleMessage],
                    model: $request->model
                );
                $conversation->save();
            }
            $conversation->messages()->create($message);


            $response = (new ChatService())->sendMessage(
                messages: $conversation->messages->map->only('role', 'content')->toArray(),
                model: $request->model
            );

            $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $response,
            ]);

            return redirect()->back()->with(['message' => $response, 'conversations' => Conversation::all()]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }
}
