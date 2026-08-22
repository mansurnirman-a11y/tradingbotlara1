<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Strategy extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'class_name',
        'webhook_key',
        'is_active',
    ];

    public function botInstances()
    {
        return $this->hasMany(BotInstance::class);
    }
}
