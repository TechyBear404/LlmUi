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
}
