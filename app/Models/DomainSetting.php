<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainSetting extends Model
{
    protected $fillable = ['domain_id', 'setting_key', 'setting_value', 'is_active'];

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }
}
