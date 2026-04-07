<?php

namespace App\Services;

use App\Models\OrderReturn;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;

class RefundService
{
    /**
     * Process a refund for an approved return.
     * Sets payment to refunded and restores product stock.
     */
    public function processRefund(OrderReturn $orderReturn): Refund
    {
        return DB::transaction(function () use ($orderReturn) {
            $payment = $orderReturn->order->payment;
            
            // Create refund record
            $refund = Refund::create([
                'order_return_id' => $orderReturn->id,
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            // Update payment status
            $payment->update([
                'status' => 'refunded',
            ]);

            // Restore stock for all items
            foreach ($orderReturn->order->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }

            // Update order return status
            $orderReturn->update([
                'status' => 'completed'
            ]);

            return $refund;
        });
    }
}
