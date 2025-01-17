<?php

namespace App\Http\Controllers;

use App\Models\CustomInstruction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomInstructionController extends Controller
{
    public function index()
    {
        $instructions = auth()->user()->customInstructions()
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('CustomInstructions/Index', [
            'instructions' => $instructions
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'about_user' => 'required|string',
            'ai_response_style' => 'required|string',
        ]);

        $instruction = auth()->user()->customInstructions()->create($validated);

        return back()->with('success', 'Custom instruction created successfully');
    }

    public function update(Request $request, CustomInstruction $instruction)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'about_user' => 'required|string',
            'ai_response_style' => 'required|string',
        ]);

        $instruction->update($validated);

        return back()->with('success', 'Custom instruction updated successfully');
    }

    public function destroy(CustomInstruction $instruction)
    {
        $instruction->delete();
        return back()->with('success', 'Custom instruction deleted successfully');
    }

    public function setActive(CustomInstruction $instruction)
    {
        // Deactivate all other instructions
        auth()->user()->customInstructions()->update(['is_active' => false]);

        // Activate the selected instruction
        $instruction->update(['is_active' => true]);

        return back()->with('success', 'Custom instruction set as active');
    }
}
