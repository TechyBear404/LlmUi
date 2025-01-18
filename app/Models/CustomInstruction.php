<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomInstruction extends Model
{
    protected $fillable = [
        'name',
        'about_user',
        'ai_response_style',
        'is_active',
        'user_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Add relationship for settings
    public function settings()
    {
        return $this->hasMany(CustomInstructionSetting::class);
    }

    public function domains()
    {
        return $this->belongsToMany(Domain::class, 'custom_instruction_domains')
            ->withPivot('is_active')
            ->withTimestamps();
    }

    // Helper method to get formatted instructions
    public function getFormattedInstructions(): string
    {
        // Base instructions with 'about_user' and 'ai_response_style'
        $baseInstructions = <<<EOT
    About User:
    {$this->about_user}

    Response Style:
    {$this->ai_response_style}
    EOT;

        // Add settings if they exist
        if ($this->settings->isNotEmpty()) {
            $baseInstructions .= "\n\nSpecific Settings:\n";
            foreach ($this->settings as $setting) {
                $baseInstructions .= "- {$setting->settingType->name}: {$setting->selectedOption->value}\n";
                if ($setting->custom_value) {
                    $baseInstructions .= "  Custom: {$setting->custom_value}\n";
                }
            }
        }

        // Add domains if they exist
        if ($this->domains->isNotEmpty()) {
            $baseInstructions .= "\n\nDomains:\n";
            foreach ($this->domains as $domain) {
                $baseInstructions .= "- {$domain->name}\n";
            }
        }

        return $baseInstructions;
    }
}
