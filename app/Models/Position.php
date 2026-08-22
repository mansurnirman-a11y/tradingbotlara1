<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $guarded = [];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function botInstance()
    {
        return $this->belongsTo(BotInstance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
