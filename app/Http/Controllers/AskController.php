<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AskController extends Controller
{
    public function index()
    {
        $models = $models = (new ChatService())->getModels();
        $conversations = Conversation::where('user_id', Auth::id())
            ->with(['messages' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }])
            ->orderBy('updated_at', 'desc')
            ->get();

        return Inertia::render('Ask/Index', [
            'models' => $models,
            'initialConversations' => $conversations
        ]);
    }

    public function ask(Request $request)
    {
        // dd($request->all());
        $validatedData = $request->validate([
            'conversation_id' => 'nullable|exists:conversations,id',
            'message' => 'required|string',
            'model' => 'required|array',
            'model.id' => 'required|string',
            'model.name' => 'required|string',
        ]);

        try {
            $message = [
                'role' => 'user',
                'content' => $validatedData['message'],
            ];
            $conversation = Conversation::findOrFail($validatedData['conversation_id']);

            $conversation->messages()->create($message);


            $response = (new ChatService())->sendMessage(
                messages: $conversation->messages->map->only('role', 'content')->toArray(),
                model: $validatedData['model']['id']
            );

            $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $response,
            ]);

            $test = Conversation::where('user_id', Auth::id())
                ->with(['messages' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }])
                ->orderBy('updated_at', 'desc')
                ->get();

            return redirect()->back()->with(['message' => $response, 'conversation' => $test]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }
}
