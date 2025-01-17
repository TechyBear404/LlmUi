<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AskController extends Controller
{
    protected $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

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
            'models' => (new ChatService())->getModels(),
            'conversations' => $conversations,
            'customInstructions' => $customInstructions,
        ]);
    }

    public function ask(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'conversation_id' => 'required|exists:conversations,id',
                'message' => 'required|string',
                'model' => 'required|array',
                'model.id' => 'required|string',
            ]);

            // Add eager loading for customInstruction
            $conversation = Conversation::with(['customInstruction', 'messages'])
                ->findOrFail($validatedData['conversation_id']);

            // Store user message
            $conversation->messages()->create([
                'content' => $validatedData['message'],
                'role' => 'user',
            ]);

            // Get AI response using conversation directly
            $response = $this->chatService->sendMessage(
                conversation: $conversation,
                model: $validatedData['model']['id']
            );

            // Store AI response
            $conversation->messages()->create([
                'content' => $response,
                'role' => 'assistant',
            ]);

            // Update title if this is the first message
            if ($conversation->messages()->count() <= 2) {
                $title = $this->chatService->makeTitle(
                    $validatedData['message'],
                    $validatedData['model']['id']
                );
                $conversation->update(['title' => $title]);
            }

            $conversation->touch();
            // Make sure to include customInstruction in fresh
            $freshConversation = $conversation->fresh(['messages', 'customInstruction']);

            return back()->with('conversation', $freshConversation);
        } catch (\Exception $e) {

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
