<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomInstructionSetting extends Model
{
    protected $fillable = [
        'setting_type_id',
        'setting_option_id',
        'custom_value',
        'domain_id'
    ];

    public function customInstruction()
    {
        return $this->belongsTo(CustomInstruction::class);
    }

    public function settingType()
    {
        return $this->belongsTo(SettingType::class);
    }

    public function selectedOption()
    {
        return $this->belongsTo(SettingOption::class, 'setting_option_id');
    }
}
