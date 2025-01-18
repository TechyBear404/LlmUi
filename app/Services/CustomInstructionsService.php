<?php

namespace App\Services;

use App\Models\CustomInstruction;
use App\Models\CustomInstructionSetting;
use App\Models\Domain;
use App\Models\SettingType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CustomInstructionsService
{
    public function getCustomInstructionForConversation(?int $customInstructionId = null): ?CustomInstruction
    {
        if (!$customInstructionId) {
            logger()->info('No custom instruction ID provided.');
            return null;
        }

        $instruction = CustomInstruction::with(['settings.settingType', 'settings.selectedOption', 'domains'])
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->find($customInstructionId);

        logger()->info('Fetched custom instruction for conversation', [
            'customInstructionId' => $customInstructionId,
            'instruction' => $instruction
        ]);

        return $instruction;
    }

    public function formatCustomInstruction(CustomInstruction $instruction): string
    {
        if ($instruction->about_user) {
            $formattedInstruction .= "À propos de l'utilisateur:\n{$instruction->about_user}\n\n";
        }

        if ($instruction->ai_response_style) {
            $formattedInstruction .= "Style de réponse souhaité:\n{$instruction->ai_response_style}\n\n";
        }

        if ($instruction->settings->isNotEmpty()) {
            $formattedInstruction .= "Paramètres spécifiques:\n";
            foreach ($instruction->settings as $setting) {
                $formattedInstruction .= "- {$setting->settingType->name}: {$setting->selectedOption->value}\n";
                if ($setting->custom_value) {
                    $formattedInstruction .= "  Personnalisé: {$setting->custom_value}\n";
                }
            }
        }

        // Add domains if they exist
        if ($instruction->domains->isNotEmpty()) {
            $formattedInstruction .= "\nDomaines d'expertise:\n";
            foreach ($instruction->domains as $domain) {
                if ($domain->pivot->is_active) {
                    $formattedInstruction .= "- {$domain->name}\n";
                    // Add domain specific settings if they exist
                    $domainSettings = $instruction->settings()
                        ->where('domain_id', $domain->id)
                        ->get();
                    foreach ($domainSettings as $setting) {
                        $formattedInstruction .= "  └ {$setting->settingType->name}: {$setting->selectedOption->value}\n";
                        if ($setting->custom_value) {
                            $formattedInstruction .= "    └ Personnalisé: {$setting->custom_value}\n";
                        }
                    }
                }
            }
        }

        logger()->info('Formatted custom instruction', [
            'formattedInstruction' => $formattedInstruction
        ]);

        return trim($formattedInstruction);
    }

    public function getAvailableSettingTypes(): Collection
    {
        return SettingType::with('options')
            ->where('is_active', true)
            ->get();
    }

    public function getAvailableDomains(): Collection
    {
        return Domain::with('settings')
            ->where('is_active', true)
            ->get();
    }

    public function validateAndFormatSettings(array $settings): array
    {
        $formattedSettings = [];
        foreach ($settings as $setting) {
            if (!isset($setting['setting_type_id']) || !isset($setting['setting_option_id'])) {
                continue;
            }

            $formattedSetting = [
                'setting_type_id' => $setting['setting_type_id'],
                'setting_option_id' => $setting['setting_option_id'],
                'custom_value' => $setting['custom_value'] ?? null,
                'domain_id' => $setting['domain_id'] ?? null
            ];

            $formattedSettings[] = $formattedSetting;
        }
        return $formattedSettings;
    }

    public function createCustomInstruction(array $data): CustomInstruction
    {
        $instruction = CustomInstruction::create([
            'name' => $data['name'],
            'about_user' => $data['about_user'] ?? null,
            'ai_response_style' => $data['ai_response_style'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'user_id' => Auth::id()
        ]);

        if (isset($data['settings'])) {
            $settings = $this->validateAndFormatSettings($data['settings']);
            foreach ($settings as $setting) {
                $instruction->settings()->create($setting);
            }
        }

        if (isset($data['domains'])) {
            $instruction->domains()->attach($data['domains']);
        }

        return $instruction->fresh(['settings', 'domains']);
    }

    public function updateCustomInstruction(CustomInstruction $instruction, array $data): CustomInstruction
    {
        $instruction->update([
            'name' => $data['name'],
            'about_user' => $data['about_user'] ?? null,
            'ai_response_style' => $data['ai_response_style'] ?? null,
            'is_active' => $data['is_active'] ?? $instruction->is_active
        ]);

        if (isset($data['settings'])) {
            // Remove old settings
            $instruction->settings()->delete();

            // Add new settings
            $settings = $this->validateAndFormatSettings($data['settings']);
            foreach ($settings as $setting) {
                $instruction->settings()->create($setting);
            }
        }

        if (isset($data['domains'])) {
            $instruction->domains()->sync($data['domains']);
        }

        return $instruction->fresh(['settings', 'domains']);
    }
}
