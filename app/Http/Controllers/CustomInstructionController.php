<?php

namespace App\Http\Controllers;

use App\Models\CustomInstruction;
use App\Models\Domain;
use App\Models\SettingType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CustomInstructionController extends Controller
{
    public function index()
    {
        $instructions = auth()->user()->customInstructions()
            ->with(['settings.settingType', 'settings.selectedOption', 'domains.settings'])
            ->orderBy('created_at', 'desc')
            ->get();

        $settingTypes = SettingType::with('options')
            ->where('is_active', true)
            ->get();

        $domains = Domain::with('settings')
            ->where('is_active', true)
            ->get();

        return Inertia::render('CustomInstructions/Index', [
            'instructions' => $instructions,
            'settingTypes' => $settingTypes,
            'domains' => $domains
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'about_user' => 'required|string',
            'ai_response_style' => 'required|string',
            'settings' => 'array',
            'domains' => 'array'
        ]);

        $instruction = auth()->user()->customInstructions()->create([
            'name' => $validated['name'],
            'about_user' => $validated['about_user'],
            'ai_response_style' => $validated['ai_response_style']
        ]);

        // Store settings if provided
        if (isset($validated['settings'])) {
            foreach ($validated['settings'] as $typeId => $setting) {
                if (!empty($setting['option_id'])) {
                    $instruction->settings()->create([
                        'setting_type_id' => $typeId,
                        'setting_option_id' => $setting['option_id'],
                        'custom_value' => $setting['custom_value'] ?? null,
                        'domain_id' => null // Explicitly set as null if not domain-specific
                    ]);
                }
            }
        }

        if (isset($validated['domains'])) {
            $instruction->domains()->attach($validated['domains']);
        }

        return back()->with('success', 'Custom instruction created successfully');
    }

    public function update(Request $request, CustomInstruction $instruction)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'about_user' => 'required|string',
            'ai_response_style' => 'required|string',
            'settings' => 'array',
            'domains' => 'array'
        ]);

        $instruction->update($validated);

        // Update settings
        if (isset($validated['settings'])) {
            // First, remove all existing settings
            $instruction->settings()->delete();

            // Then create new settings
            foreach ($validated['settings'] as $typeId => $setting) {
                if (!empty($setting['option_id'])) {
                    $instruction->settings()->create([
                        'setting_type_id' => $typeId,
                        'setting_option_id' => $setting['option_id'],
                        'custom_value' => $setting['custom_value'] ?? null,
                        'domain_id' => null // Explicitly set as null if not domain-specific
                    ]);
                }
            }
        }

        if (isset($validated['domains'])) {
            $instruction->domains()->sync($validated['domains']);
        }

        return back()->with('success', 'Custom instruction updated successfully');
    }

    public function destroy(CustomInstruction $instruction)
    {
        $instruction->delete();
        return back()->with('success', 'Custom instruction deleted successfully');
    }
}
