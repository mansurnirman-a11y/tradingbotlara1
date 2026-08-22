<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    const UPDATED_AT = null; // Audit logs are immutable, no updated_at

    protected $fillable = [
        'user_id',
        'action',
        'resource_type',
        'resource_id',
        'payload',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
