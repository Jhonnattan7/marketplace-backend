<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Admin
            'manage-users',
            'suspend-product',
            'view-all-orders',
            'view-all-payments',
            'manage-returns',

            // Vendedor
            'create-product',
            'edit-own-product',
            'delete-own-product',
            'view-seller-orders',
            'view-received-payments',
            'view-return-status',

            // Comprador
            'create-order',
            'manage-cart',
            'create-return',
            'view-own-orders',
            'view-own-payments',

            // Compartidos
            'view-products',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $vendedor = Role::firstOrCreate(['name' => 'vendedor']);
        $comprador = Role::firstOrCreate(['name' => 'comprador']);

        $admin->syncPermissions([
            'manage-users',
            'suspend-product',
            'view-all-orders',
            'view-all-payments',
            'manage-returns',
            'view-products',
        ]);

        $vendedor->syncPermissions([
            'create-product',
            'edit-own-product',
            'delete-own-product',
            'view-seller-orders',
            'view-received-payments',
            'view-return-status',
            'view-products',
        ]);

        $comprador->syncPermissions([
            'create-order',
            'manage-cart',
            'create-return',
            'view-own-orders',
            'view-own-payments',
            'view-return-status',
            'view-products',
        ]);
    }
}
