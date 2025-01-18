<?php

namespace Database\Seeders;

use App\Models\SettingType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Communication Style
        $styleType = SettingType::create([
            'name' => 'communication_style',
            'description' => 'Defines how the AI should communicate'
        ]);

        $styleType->options()->createMany([
            [
                'name' => 'professional',
                'value' => 'Respond in a professional and formal manner',
                'is_default' => true
            ],
            [
                'name' => 'casual',
                'value' => 'Use a casual, conversational tone',
                'is_default' => false
            ],
            [
                'name' => 'academic',
                'value' => 'Use academic language and cite sources when possible',
                'is_default' => false,
                'is_active' => true
            ],
            [
                'name' => 'technical',
                'value' => 'Focus on technical details and precision',
                'is_default' => false,
                'is_active' => true
            ]
        ]);

        // Response Length
        $lengthType = SettingType::create([
            'name' => 'response_length',
            'description' => 'Preferred length of AI responses',
            'is_active' => true
        ]);

        $lengthType->options()->createMany([
            [
                'name' => 'concise',
                'value' => 'Provide brief, to-the-point responses',
                'is_default' => false,
                'is_active' => true
            ],
            [
                'name' => 'balanced',
                'value' => 'Balance between detail and brevity',
                'is_default' => true,
                'is_active' => true
            ],
            [
                'name' => 'detailed',
                'value' => 'Provide comprehensive, detailed responses',
                'is_default' => false,
                'is_active' => true
            ]
        ]);

        // Expertise Level
        $expertiseType = SettingType::create([
            'name' => 'expertise_level',
            'description' => 'Level of technical expertise in responses',
            'is_active' => true
        ]);

        $expertiseType->options()->createMany([
            [
                'name' => 'beginner',
                'value' => 'Explain concepts in simple terms for beginners',
                'is_default' => false,
                'is_active' => true
            ],
            [
                'name' => 'intermediate',
                'value' => 'Balance technical detail with accessibility',
                'is_default' => true,
                'is_active' => true
            ],
            [
                'name' => 'expert',
                'value' => 'Use advanced terminology and concepts',
                'is_default' => false,
                'is_active' => true
            ]
        ]);
    }
}
