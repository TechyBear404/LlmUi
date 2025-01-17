<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

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
        try {
            // Log::info('Creating new conversation:', $request->all());

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

            // Log::info('Conversation created:', [
            //     'id' => $conversation->id,
            //     'model' => $validatedData['model']
            // ]);

            return back()->with('conversation', $conversation);
        } catch (\Exception $e) {
            // Log::error('Error creating conversation:', [
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function updateModel(Request $request, Conversation $conversation)
    {
        try {
            // Log::info('Updating conversation model:', $request->all());

            $validatedData = $request->validate([
                'model' => 'required|array',
                'model.id' => 'required|string',
                'model.name' => 'required|string',
            ]);

            $conversation->update([
                'model_id' => $validatedData['model']['id'],
                'model_name' => $validatedData['model']['name'],
            ]);

            $conversation->load('messages');

            // Log::info('Model updated successfully:', [
            //     'conversation_id' => $conversation->id,
            //     'new_model' => $validatedData['model']
            // ]);

            return back()->with('conversation', $conversation);
        } catch (\Exception $e) {
            // Log::error('Error updating model:', [
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function updateCustomInstruction(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'custom_instruction_id' => 'nullable|exists:custom_instructions,id'
        ]);

        $conversation->update([
            'custom_instruction_id' => $validated['custom_instruction_id']
        ]);

        return back()->with('success', 'Custom instruction updated');
    }

    public function destroy(Conversation $conversation)
    {
        $conversation->messages()->delete();
        $conversation->delete();

        return back()->with(['message' => 'Conversation deleted successfully']);
    }
}
