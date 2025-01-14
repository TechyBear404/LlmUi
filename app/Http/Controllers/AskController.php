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
        return Inertia::render('Ask/Index', [
            'models' => (new ChatService())->getModels(),
            'conversations' => auth()->user()->conversations()
                ->with(['messages' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }])
                ->orderBy('updated_at', 'desc')
                ->get() ?? [],
        ]);
    }

    public function ask(Request $request)
    {
        // dd($request->all());
        try {
            $validatedData = $request->validate([
                'conversation_id' => 'required|exists:conversations,id',
                'message' => 'required|string',
                'model' => 'required|array',
                'model.id' => 'required|string',
                'model.name' => 'required|string',
            ]);

            $conversation = Conversation::findOrFail($validatedData['conversation_id']);

            // Store user message
            $conversation->messages()->create([
                'content' => $validatedData['message'],
                'role' => 'user',
            ]);

            // Get conversation history
            $messageHistory = $conversation->messages()
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn($message) => [
                    'role' => $message->role,
                    'content' => $message->content,
                ])
                ->toArray();

            // Get AI response
            $response = (new ChatService())->sendMessage(
                messages: $messageHistory,
                model: $validatedData['model']['id']
            );

            // Store AI response
            $conversation->messages()->create([
                'content' => $response,
                'role' => 'assistant',
            ]);

            // Update title if this is the first message
            if ($conversation->messages()->count() <= 2) {
                $title = (new ChatService())->makeTitle(
                    messages: $validatedData['message'],
                    model: $validatedData['model']['id']
                );
                $conversation->update(['title' => $title]);
            }

            $conversation->touch();

            return back()->with([
                'conversation' => $conversation->fresh()->load('messages'),
            ]);
        } catch (\Exception $e) {
            logger()->error('Error in ask:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all() // Add this for debugging
            ]);
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
