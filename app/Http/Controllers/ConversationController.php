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
                $query->orderBy('created_at', 'asc'); // Changed to ascending order
            }])
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($conversations);
    }

    public function show(Conversation $conversation)
    {
        // Check if user owns the conversation
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }

        $conversation->load(['messages' => function ($query) {
            $query->orderBy('created_at', 'asc'); // Keep ascending order for consistency
        }]);

        return response()->json($conversation);
    }

    public function store(Request $request)
    {
        // dd($request->all());
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

            return redirect()->back()->with('success', 'Conversation created successfully')->with('conversation', $conversation);
        } catch (\Exception $e) {
            logger()->error('Error creating conversation:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }



    public function updateModel(Request $request, Conversation $conversation)
    {
        try {
            $validatedData = $request->validate([
                'model' => 'required|array',
                'model.id' => 'required|string',
                'model.name' => 'required|string',
            ]);

            $conversation->update([
                'model_id' => $validatedData['model']['id'],
                'model_name' => $validatedData['model']['name'],
            ]);

            return back()->with([
                'conversation' => $conversation->load('messages')
            ]);
        } catch (\Exception $e) {
            logger()->error('Error updating conversation model:', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage()
            ]);
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(Conversation $conversation)
    {
        $conversation->messages()->delete();
        $conversation->delete();

        return back()->with(['message' => 'Conversation deleted successfully']);
    }
}
