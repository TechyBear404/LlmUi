<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingOption extends Model
{
    protected $fillable = ['setting_type_id', 'name', 'value', 'is_default', 'is_active'];

    public function type()
    {
        return $this->belongsTo(SettingType::class, 'setting_type_id');
    }
}
