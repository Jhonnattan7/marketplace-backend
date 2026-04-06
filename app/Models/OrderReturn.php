<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReturn extends Model
{
    protected $fillable = [
        'buyer_id',
        'order_id',
        'reason',
        'status',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'resolved_at' => now(),
        ]);

        $this->order->payment->markAsRefunded();
    }

    public function reject(): void
    {
        $this->update([
            'status' => 'rejected',
            'resolved_at' => now(),
        ]);
    }
}
