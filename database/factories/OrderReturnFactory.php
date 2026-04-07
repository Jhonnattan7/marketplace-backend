<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderReturn>
 */
class OrderReturnFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'buyer_id' => User::factory(),
            'reason' => 'Defective item, does not turn on. Sent back.',
            'status' => 'pending',
            'resolved_at' => null,
            'admin_notes' => null,
        ];
    }
}
