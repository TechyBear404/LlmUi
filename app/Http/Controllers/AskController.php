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
        // $currentConversation get the conversation from DB where more recent message
        $currentConversation = $conversations->last()->load('messages');

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
            if ($request->conversation_id) {
                $conversation = Conversation::findOrFail($request->conversation_id);
            } else {
                $conversation = Conversation::create();
            }
            $message = [
                'role' => 'user',
                'content' => $request->message,
            ];
            $conversation->messages()->create($message);

            $response = (new ChatService())->sendMessage(
                messages: $conversation->messages->map->only('role', 'content')->toArray(),
                model: $request->model
            );

            $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $response,
            ]);

            return redirect()->back()->with('message', $response);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }
}
