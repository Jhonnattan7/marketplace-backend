<?php
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    app('Spatie\Permission\PermissionRegistrar')->forgetCachedPermissions();

    $permissionModel = config('permission.models.permission');
    $roleModel       = config('permission.models.role');

    $permissions = [
        'manage-users', 'suspend-product', 'view-all-orders', 'view-all-payments',
        'manage-returns', 'create-product', 'edit-own-product', 'delete-own-product',
        'view-seller-orders', 'view-received-payments', 'view-return-status',
        'create-order', 'manage-cart', 'create-return', 'view-own-orders',
        'view-own-payments', 'view-products',
    ];

    foreach ($permissions as $perm) {
        $permissionModel::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }

    foreach (['admin', 'seller', 'buyer'] as $role) {
        $roleModel::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    $roleModel::findByName('buyer', 'web')->syncPermissions([
        'create-order', 'manage-cart', 'create-return',
        'view-own-orders', 'view-own-payments', 'view-return-status', 'view-products',
    ]);

    $roleModel::findByName('seller', 'web')->syncPermissions([
        'create-product', 'edit-own-product', 'delete-own-product',
        'view-seller-orders', 'view-received-payments', 'view-return-status', 'view-products',
    ]);

    $roleModel::findByName('admin', 'web')->syncPermissions([
        'manage-users', 'suspend-product', 'view-all-orders',
        'view-all-payments', 'manage-returns', 'view-products',
    ]);
});

test('flujo completo: venta → compra → devolución', function () {
    // ── Setup actores ────────────────────────────────────────
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $profile = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Tienda E2E']);

    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');
    \App\Models\BuyerProfile::create(['user_id' => $buyer->id]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $category = Category::factory()->create();

    // ── FLUJO DE VENTA: vendedor publica producto ────────────
    $productResponse = $this->actingAs($seller)->postJson('/api/products', [
        'name'        => 'Producto E2E',
        'price'       => 200.00,
        'stock'       => 5,
        'category_id' => $category->id,
    ]);
    $productResponse->assertStatus(201);
    $productId = $productResponse->json('id') ?? $productResponse->json('data.id');

    // Verificar que el producto es público
    $this->getJson('/api/products')->assertStatus(200);

    // ── FLUJO DE COMPRA: comprador hace pedido y paga ────────
    $orderResponse = $this->actingAs($buyer)->postJson('/api/orders', [
        'items' => [['product_id' => $productId, 'quantity' => 1]],
    ]);
    $orderResponse->assertStatus(201);
    $orderId = $orderResponse->json('data.id');

    $paymentResponse = $this->actingAs($buyer)->postJson('/api/payments', [
        'order_id' => $orderId,
        'method'   => 'credit_card',
    ]);
    $paymentResponse->assertStatus(201);

    // Vendedor procesa el pedido hasta entregado
    $this->actingAs($seller)->patchJson("/api/seller/orders/{$orderId}/status", ['status' => 'shipped'])
        ->assertStatus(200);
    $this->actingAs($seller)->patchJson("/api/seller/orders/{$orderId}/status", ['status' => 'delivered'])
        ->assertStatus(200);

    // ── FLUJO DE DEVOLUCIÓN: comprador solicita devolución ───
    $returnResponse = $this->actingAs($buyer)->postJson('/api/returns', [
        'order_id' => $orderId,
        'reason'   => 'El producto llegó dañado y no funciona correctamente.',
    ]);
    $returnResponse->assertStatus(201)
        ->assertJsonPath('data.status', 'pending');

    $returnId = $returnResponse->json('data.id');

    // Admin aprueba la devolución
    $this->actingAs($admin)->putJson("/api/admin/returns/{$returnId}/status", [
        'status' => 'approved',
    ])->assertStatus(200);

    // Verificar estado final en base de datos
    expect(Order::find($orderId)->status)->toBe('delivered');
    $this->assertDatabaseHas('order_returns', ['id' => $returnId, 'status' => 'completed']);
});