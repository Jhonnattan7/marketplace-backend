<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'order_id',
        'reason',
        'status',
        'admin_notes',
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

    public function refund()
    {
        return $this->hasOne(Refund::class);
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'resolved_at' => now(),
        ]);
        
        // Stock restoration and refund are handled via RefundService
    }

    public function reject(?string $notes = null): void
    {
        $this->update([
            'status' => 'rejected',
            'admin_notes' => $notes,
            'resolved_at' => now(),
        ]);
    }
}
