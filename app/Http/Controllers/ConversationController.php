<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ConversationController extends Controller
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
                $query->orderBy('created_at', 'desc');
            }])
            ->orderBy('updated_at', 'desc')
            ->get();
        $conversations = Conversation::where('user_id', Auth::id())
            ->with(['messages' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }])
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($conversations);
    }

    public function show(Conversation $conversation)
    {
        $conversation->load(['messages' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }]);

        return response()->json($conversation);
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'model' => 'required|array',
                'model.id' => 'required|string',
                'model.name' => 'required|string',
            ]);

            $conversation = Conversation::create([
                'title' => 'Nouvelle conversation ...',
                'model_id' => $validatedData['model']['id'],
                'model_name' => $validatedData['model']['name'],
                'user_id' => Auth::id(),
            ]);

            logger()->info('Empty conversation created successfully', [
                'conversation_id' => $conversation->id,
                'model' => $validatedData['model']
            ]);

            return redirect()->back()->with('conversation', $conversation);
        } catch (\Exception $e) {
            logger()->error('Error creating conversation:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            return redirect()->back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function addMessage(Request $request, Conversation $conversation)
    {
        dd($request->all());
        try {
            logger()->info('Adding message to conversation', [
                'conversation_id' => $conversation->id,
                'user_id' => Auth::id(),
            ]);

            $validatedData = $request->validate([
                'message' => 'required|string',
                'model' => 'required|array',
            ]);

            if ($request->model->id !== Auth::user()->model_id) {
                $user = User::find(Auth::id());
                $user->update([
                    'model_id' => $request->model->id,
                    'model_name' => $request->model->name,
                ]);
            }
            // Store user message
            $userMessage = $conversation->messages()->create([
                'content' => $validatedData['message'],
                'role' => 'user',
            ]);

            // Get conversation history
            $messageHistory = $conversation->messages()
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($message) {
                    return [
                        'role' => $message->role,
                        'content' => $message->content,
                    ];
                })
                ->toArray();

            try {
                $aiResponse = $this->chatService->sendMessage(
                    $messageHistory,
                    $validatedData['model']
                );

                // Store AI response
                $conversation->messages()->create([
                    'content' => $aiResponse,
                    'role' => 'assistant',
                ]);

                // Generate title if this is the first message
                if ($conversation->messages()->count() <= 2) {
                    $title = $this->chatService->makeTitle($validatedData['message'], $validatedData['model']);
                    $conversation->update(['title' => $title]);
                }

                $conversation->touch();

                logger()->info('Message added successfully', [
                    'conversation_id' => $conversation->id,
                    'message_count' => $conversation->messages()->count()
                ]);

                return response()->json([
                    'conversation' => $conversation->load('messages'),
                    'message' => 'Message added successfully'
                ]);
            } catch (\Exception $e) {
                logger()->error('Error adding message:', [
                    'conversation_id' => $conversation->id,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json([
                    'error' => $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            logger()->error('Error adding message:', [
                'conversation_id' => $conversation->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Conversation $conversation)
    {
        $conversation->messages()->delete();
        $conversation->delete();

        return response()->json(['message' => 'Conversation deleted successfully']);
    }
}
