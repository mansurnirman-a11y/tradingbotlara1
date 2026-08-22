<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    protected $fillable = [
        'bot_instance_id',
        'user_id',
        'order_id',
        'symbol',
        'side',
        'type',
        'price',
        'quantity',
        'volume_usd',
        'fee_paid',
        'realized_pnl',
        'status',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:10',
            'quantity' => 'decimal:10',
            'fee_paid' => 'decimal:10',
            'realized_pnl' => 'decimal:10',
            'executed_at' => 'datetime',
        ];
    }

    public function botInstance()
    {
        return $this->belongsTo(BotInstance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
