<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'buyer_id' => \App\Models\BuyerProfile::factory(),
            'status'   => 'pending',
            'total'    => $this->faker->randomFloat(2, 10, 5000),
            'notes'    => $this->faker->optional()->sentence(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn() => ['status' => 'paid']);
    }

    public function shipped(): static
    {
        return $this->state(fn() => ['status' => 'shipped']);
    }

    public function delivered(): static
    {
        return $this->state(fn() => ['status' => 'delivered']);
    }

    public function cancelled(): static
    {
        return $this->state(fn() => ['status' => 'cancelled']);
    }
}
