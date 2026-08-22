<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrokerAccount extends Model
{
    protected $fillable = [
        'user_id',
        'broker',
        'account_label',
        'api_key',
        'api_secret',
        'bridge_url',
        'meta_account_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'api_secret' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function botInstances()
    {
        return $this->hasMany(BotInstance::class);
    }
}
