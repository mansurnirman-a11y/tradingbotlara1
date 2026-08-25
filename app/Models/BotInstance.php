<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotInstance extends Model
{
    protected $fillable = [
        'user_id',
        'broker_account_id',
        'strategy_id',
        'name',
        'symbol',
        'strategy_class', // keeping for legacy fallback or direct class ref
        'timeframe',
        'allocated_capital',
        'max_drawdown_pct',
        'parameters',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'allocated_capital' => 'decimal:10',
            'max_drawdown_pct' => 'decimal:2',
            'parameters' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function brokerAccount()
    {
        return $this->belongsTo(BrokerAccount::class);
    }

    public function strategy()
    {
        return $this->belongsTo(Strategy::class);
    }

    public function trades()
    {
        return $this->hasMany(Trade::class);
    }

    public function positions()
    {
        return $this->hasMany(Position::class);
    }
}
