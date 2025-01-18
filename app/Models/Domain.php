<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    protected $fillable = ['name', 'description', 'is_active'];

    public function settings()
    {
        return $this->hasMany(DomainSetting::class);
    }
}
