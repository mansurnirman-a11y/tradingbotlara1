<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BrokerAccount extends Model
{
    use SoftDeletes;
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

    /**
     * Get effective leverage for the broker account
     */
    public function getEffectiveLeverage(?float $override = null): float
    {
        if ($override !== null && $override > 0) {
            return (float) $override;
        }

        return match (strtolower($this->broker ?? '')) {
            'oanda' => 25.0,
            'delta', 'delta_india' => 20.0,
            'binance', 'bybit' => 20.0,
            'mt4', 'mt5' => 100.0,
            default => 25.0,
        };
    }
}
