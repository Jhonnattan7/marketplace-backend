<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app('Spatie\\Permission\\PermissionRegistrar')->forgetCachedPermissions();

        $permissionModel = config('permission.models.permission');
        $roleModel = config('permission.models.role');

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
            $permissionModel::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $admin = $roleModel::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $vendedor = $roleModel::firstOrCreate(['name' => 'vendedor', 'guard_name' => 'web']);
        $comprador = $roleModel::firstOrCreate(['name' => 'comprador', 'guard_name' => 'web']);
        $seller = $roleModel::firstOrCreate(['name' => 'seller', 'guard_name' => 'web']);
        $buyer = $roleModel::firstOrCreate(['name' => 'buyer', 'guard_name' => 'web']);

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

        $seller->syncPermissions([
            'create-product',
            'edit-own-product',
            'delete-own-product',
            'view-seller-orders',
            'view-received-payments',
            'view-return-status',
            'view-products',
        ]);

        $buyer->syncPermissions([
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
