<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id'      => \App\Models\Order::factory(),
            'method'        => $this->faker->randomElement(['credit_card', 'paypal', 'transfer', 'cash']),
            'status'        => 'pending',
            'amount'        => $this->faker->randomFloat(2, 10, 5000),
            'transaction_id' => null,
            'paid_at'       => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn() => [
            'status' => 'completed',
            'transaction_id' => $this->faker->uuid(),
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn() => ['status' => 'failed']);
    }

    public function refunded(): static
    {
        return $this->state(fn() => ['status' => 'refunded']);
    }
}