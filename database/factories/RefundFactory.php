<?php

namespace Database\Factories;

use App\Models\OrderReturn;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Refund>
 */
class RefundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_return_id' => OrderReturn::factory(),
            'payment_id' => function(array $attributes) {
                // Ensure payment corresponds to the order connected to the return
                $return = OrderReturn::find($attributes['order_return_id']) ?? OrderReturn::factory()->create();
                
                $id = DB::table('payments')->insertGetId([
                    'order_id' => $return->order_id,
                    'amount' => 100.00,
                    'status' => 'completed',
                    'method' => 'credit_card',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return $id;
            },
            'amount' => 100.00,
            'status' => 'pending',
            'processed_at' => null,
        ];
    }
}
