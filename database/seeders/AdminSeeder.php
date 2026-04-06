<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@marketplace.com'],
            [
                'name'     => 'Administrador',
                'password' => 'password',
                'phone'    => null,
            ]
        );

        $admin->assignRole('admin');
    }
}
