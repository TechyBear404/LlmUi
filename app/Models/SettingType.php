<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingType extends Model
{
    protected $fillable = ['name', 'description', 'is_active'];

    public function options()
    {
        return $this->hasMany(SettingOption::class);
    }

    public function userSettings()
    {
        return $this->hasMany(UserSetting::class);
    }
}
