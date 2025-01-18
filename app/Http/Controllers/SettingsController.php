<?php

namespace App\Http\Controllers;

use App\Models\SettingType;
use App\Services\InstructionSettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function __construct(private InstructionSettingsService $settingsService) {}

    public function index()
    {
        return Inertia::render('Settings/Index', [
            'userSettings' => $this->settingsService->getUserSettings(auth()->user()),
            'availableSettings' => SettingType::with('options')->get()
        ]);
    }

    public function update(Request $request)
    {
        foreach ($request->settings as $typeId => $optionId) {
            $this->settingsService->updateUserSetting(
                auth()->user(),
                $typeId,
                $optionId
            );
        }

        return redirect()->back()
            ->with('message', 'Settings updated successfully');
    }
}
