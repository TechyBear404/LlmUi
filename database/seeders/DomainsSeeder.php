<?php

namespace Database\Seeders;

use App\Models\Domain;
use App\Models\DomainSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DomainsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $domains = [
            [
                'name' => 'Programming',
                'description' => 'Software development and coding topics',
                'is_active' => true,
                'settings' => [
                    ['setting_key' => 'code_style', 'setting_value' => 'Use clear code examples with comments'],
                    ['setting_key' => 'documentation_style', 'setting_value' => 'Follow PSR standards for PHP'],
                ]
            ],
            [
                'name' => 'Data Science',
                'description' => 'Data analysis, statistics, and machine learning',
                'is_active' => true,
                'settings' => [
                    ['setting_key' => 'visualization', 'setting_value' => 'Include data visualization suggestions'],
                    ['setting_key' => 'statistical_detail', 'setting_value' => 'Explain statistical concepts thoroughly'],
                ]
            ],
            [
                'name' => 'Business Analysis',
                'description' => 'Business strategy and analysis topics',
                'is_active' => true,
                'settings' => [
                    ['setting_key' => 'framework', 'setting_value' => 'Use standard business frameworks'],
                    ['setting_key' => 'metrics', 'setting_value' => 'Focus on KPIs and measurable outcomes'],
                ]
            ],
            [
                'name' => 'Project Management',
                'description' => 'Project planning and execution topics',
                'is_active' => true,
                'settings' => [
                    ['setting_key' => 'methodology', 'setting_value' => 'Follow PMI standards'],
                    ['setting_key' => 'documentation', 'setting_value' => 'Include project documentation templates'],
                ]
            ],
            [
                'name' => 'Technical Writing',
                'description' => 'Documentation and technical communication',
                'is_active' => true,
                'settings' => [
                    ['setting_key' => 'format', 'setting_value' => 'Follow technical writing best practices'],
                    ['setting_key' => 'audience', 'setting_value' => 'Adapt content for technical audiences'],
                ]
            ]
        ];

        foreach ($domains as $domainData) {
            $settings = $domainData['settings'];
            unset($domainData['settings']);

            $domain = Domain::create($domainData);

            foreach ($settings as $setting) {
                DomainSetting::create([
                    'domain_id' => $domain->id,
                    'setting_key' => $setting['setting_key'],
                    'setting_value' => $setting['setting_value'],
                    'is_active' => true
                ]);
            }
        }
    }
}
