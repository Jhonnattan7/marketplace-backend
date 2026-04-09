<?php

namespace Database\Factories;

use App\Models\BuyerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BuyerProfile>
 */
class BuyerProfileFactory extends Factory
{
    protected $model = BuyerProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            // Agrega otros campos si tu tabla buyer_profiles los tiene (ej. preferencias, dirección)
        ];
    }
}
