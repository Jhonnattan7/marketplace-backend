<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Helpers ──────────────────────────────────────────────────

beforeEach(function () {
    app('Spatie\\Permission\\PermissionRegistrar')->forgetCachedPermissions();

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

    foreach (['admin', 'seller', 'buyer', 'vendedor', 'comprador'] as $role) {
        $roleModel::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    $roleModel::findByName('buyer', 'web')->syncPermissions([
        'create-order', 'manage-cart', 'create-return',
        'view-own-orders', 'view-own-payments', 'view-return-status', 'view-products',
    ]);

    $roleModel::findByName('comprador', 'web')->syncPermissions([
        'create-order', 'manage-cart', 'create-return',
        'view-own-orders', 'view-own-payments', 'view-return-status', 'view-products',
    ]);

    $roleModel::findByName('seller', 'web')->syncPermissions([
        'create-product', 'edit-own-product', 'delete-own-product',
        'view-seller-orders', 'view-received-payments', 'view-return-status', 'view-products',
    ]);

    $roleModel::findByName('vendedor', 'web')->syncPermissions([
        'create-product', 'edit-own-product', 'delete-own-product',
        'view-seller-orders', 'view-received-payments', 'view-return-status', 'view-products',
    ]);

    $roleModel::findByName('admin', 'web')->syncPermissions([
        'manage-users', 'suspend-product', 'view-all-orders',
        'view-all-payments', 'manage-returns', 'view-products',
    ]);
});

function createBuyer(): User
{
    $user = User::factory()->create();
    $user->assignRole('buyer');
    return $user;
}

function createSeller(): User
{
    $user = User::factory()->create();
    $user->assignRole('seller');
    SellerProfile::factory()->create(['user_id' => $user->id]);
    return $user;
}

function createProductForSeller(User $seller, array $attrs = []): Product
{
    $profile = $seller->sellerProfile;
    return Product::factory()->create(array_merge([
        'seller_profile_id' => $profile->id,
        'status'            => 'active',
        'stock'             => 50,
    ], $attrs));
}


// ── POST /orders — Crear Pedido (Comprador) ─────────────────

test('comprador puede crear un pedido exitosamente', function () {
    $buyer  = createBuyer();
    $seller = createSeller();
    $product1 = createProductForSeller($seller, ['price' => 100.00, 'stock' => 10]);
    $product2 = createProductForSeller($seller, ['price' => 50.00, 'stock' => 20]);

    $response = $this->actingAs($buyer)->postJson('/api/orders', [
        'notes' => 'Entregar rápido',
        'items' => [
            ['product_id' => $product1->id, 'quantity' => 2],
            ['product_id' => $product2->id, 'quantity' => 3],
        ],
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.total', '350.00');

    // Verify stock was decremented
    expect($product1->fresh()->stock)->toBe(8);
    expect($product2->fresh()->stock)->toBe(17);

    $this->assertDatabaseCount('orders', 1);
    $this->assertDatabaseCount('order_items', 2);
});

test('crear pedido sin items retorna 422', function () {
    $buyer = createBuyer();

    $response = $this->actingAs($buyer)->postJson('/api/orders', [
        'items' => [],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items']);
});

test('crear pedido con stock insuficiente retorna 422', function () {
    $buyer   = createBuyer();
    $seller  = createSeller();
    $product = createProductForSeller($seller, ['price' => 100.00, 'stock' => 2]);

    $response = $this->actingAs($buyer)->postJson('/api/orders', [
        'items' => [
            ['product_id' => $product->id, 'quantity' => 5],
        ],
    ]);

    $response->assertStatus(422);
});

test('vendedor no puede crear un pedido → 403', function () {
    $seller  = createSeller();
    $seller2 = createSeller();
    $product = createProductForSeller($seller2);

    $response = $this->actingAs($seller)->postJson('/api/orders', [
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ]);

    $response->assertStatus(403);
});

test('usuario no autenticado no puede crear pedido → 401', function () {
    $response = $this->postJson('/api/orders', [
        'items' => [['product_id' => 1, 'quantity' => 1]],
    ]);

    $response->assertStatus(401);
});


// ── GET /orders — Listar mis pedidos ────────────────────────

test('comprador puede listar sus pedidos', function () {
    $buyer  = createBuyer();
    $seller = createSeller();
    $product = createProductForSeller($seller);

    // Create 2 orders for this buyer
    for ($i = 0; $i < 2; $i++) {
        $order = Order::factory()->create(['buyer_id' => $buyer->id, 'total' => 100]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity'   => 1,
            'unit_price' => 100,
        ]);
    }

    // Create 1 order for another buyer (should not appear)
    $otherBuyer = createBuyer();
    Order::factory()->create(['buyer_id' => $otherBuyer->id, 'total' => 50]);

    $response = $this->actingAs($buyer)->getJson('/api/orders');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
});


// ── GET /orders/{id} — Detalle de pedido ────────────────────

test('comprador puede ver detalle de su pedido', function () {
    $buyer   = createBuyer();
    $seller  = createSeller();
    $product = createProductForSeller($seller, ['price' => 200]);

    $order = Order::factory()->create(['buyer_id' => $buyer->id, 'total' => 400]);
    $order->items()->create([
        'product_id' => $product->id,
        'quantity'   => 2,
        'unit_price' => 200,
    ]);

    $response = $this->actingAs($buyer)->getJson("/api/orders/{$order->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $order->id)
        ->assertJsonPath('data.total', '400.00');
});

test('comprador no puede ver pedido de otro comprador → 403', function () {
    $buyer1      = createBuyer();
    $buyer2      = createBuyer();
    $seller      = createSeller();
    $product     = createProductForSeller($seller);

    $order = Order::factory()->create(['buyer_id' => $buyer1->id, 'total' => 100]);
    $order->items()->create([
        'product_id' => $product->id,
        'quantity'   => 1,
        'unit_price' => 100,
    ]);

    $response = $this->actingAs($buyer2)->getJson("/api/orders/{$order->id}");

    $response->assertStatus(403);
});


// ── GET /seller/orders — Pedidos del vendedor ───────────────

test('vendedor ve pedidos que contienen sus productos', function () {
    $buyer   = createBuyer();
    $seller1 = createSeller();
    $seller2 = createSeller();
    $product1 = createProductForSeller($seller1);
    $product2 = createProductForSeller($seller2);

    // Order with seller1's product
    $order1 = Order::factory()->create(['buyer_id' => $buyer->id, 'total' => 100]);
    $order1->items()->create([
        'product_id' => $product1->id,
        'quantity'   => 1,
        'unit_price' => 100,
    ]);

    // Order with seller2's product (seller1 shouldn't see this)
    $order2 = Order::factory()->create(['buyer_id' => $buyer->id, 'total' => 200]);
    $order2->items()->create([
        'product_id' => $product2->id,
        'quantity'   => 1,
        'unit_price' => 200,
    ]);

    $response = $this->actingAs($seller1)->getJson('/api/seller/orders');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});


// ── PUT /seller/orders/{id}/status — Actualizar estado ──────

test('vendedor puede cambiar estado de pedido paid → shipped', function () {
    $buyer   = createBuyer();
    $seller  = createSeller();
    $product = createProductForSeller($seller);

    $order = Order::factory()->create([
        'buyer_id' => $buyer->id,
        'status'   => 'paid',
        'total'    => 100,
    ]);
    $order->items()->create([
        'product_id' => $product->id,
        'quantity'   => 1,
        'unit_price' => 100,
    ]);

    $response = $this->actingAs($seller)->patchJson("/api/seller/orders/{$order->id}/status", [
        'status' => 'shipped',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'shipped');
});

test('no se puede hacer transición inválida pendiente → shipped', function () {
    $buyer   = createBuyer();
    $seller  = createSeller();
    $product = createProductForSeller($seller);

    $order = Order::factory()->create([
        'buyer_id' => $buyer->id,
        'status'   => 'pending',
        'total'    => 100,
    ]);
    $order->items()->create([
        'product_id' => $product->id,
        'quantity'   => 1,
        'unit_price' => 100,
    ]);

    $response = $this->actingAs($seller)->patchJson("/api/seller/orders/{$order->id}/status", [
        'status' => 'shipped',
    ]);

    $response->assertStatus(422);
});

test('cancelar pedido restaura el stock', function () {
    $buyer   = createBuyer();
    $seller  = createSeller();
    $product = createProductForSeller($seller, ['stock' => 10]);

    $order = Order::factory()->create([
        'buyer_id' => $buyer->id,
        'status'   => 'pending',
        'total'    => 200,
    ]);
    $order->items()->create([
        'product_id' => $product->id,
        'quantity'   => 3,
        'unit_price' => 66.67,
    ]);

    $this->actingAs($seller)->patchJson("/api/seller/orders/{$order->id}/status", [
        'status' => 'cancelled',
    ]);

    expect($product->fresh()->stock)->toBe(13);
});


// ── POST /payments — Procesar pago ──────────────────────────

test('comprador puede pagar su pedido pendiente', function () {
    $buyer   = createBuyer();
    $seller  = createSeller();
    $product = createProductForSeller($seller, ['price' => 100]);

    $order = Order::factory()->create([
        'buyer_id' => $buyer->id,
        'status'   => 'pending',
        'total'    => 200,
    ]);
    $order->items()->create([
        'product_id' => $product->id,
        'quantity'   => 2,
        'unit_price' => 100,
    ]);

    $response = $this->actingAs($buyer)->postJson('/api/payments', [
        'order_id' => $order->id,
        'method'   => 'credit_card',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.amount', '200.00');

    // Order status should be 'paid'
    expect($order->fresh()->status)->toBe('paid');

    $this->assertDatabaseCount('payments', 1);
});

test('no se puede pagar un pedido ya pagado', function () {
    $buyer   = createBuyer();
    $seller  = createSeller();
    $product = createProductForSeller($seller, ['price' => 100]);

    $order = Order::factory()->create([
        'buyer_id' => $buyer->id,
        'status'   => 'paid',
        'total'    => 100,
    ]);
    $order->items()->create([
        'product_id' => $product->id,
        'quantity'   => 1,
        'unit_price' => 100,
    ]);

    $response = $this->actingAs($buyer)->postJson('/api/payments', [
        'order_id' => $order->id,
        'method'   => 'paypal',
    ]);

    $response->assertStatus(422);
});

test('comprador no puede pagar pedido de otro comprador → 403', function () {
    $buyer1  = createBuyer();
    $buyer2  = createBuyer();
    $seller  = createSeller();
    $product = createProductForSeller($seller, ['price' => 100]);

    $order = Order::factory()->create([
        'buyer_id' => $buyer1->id,
        'status'   => 'pending',
        'total'    => 100,
    ]);
    $order->items()->create([
        'product_id' => $product->id,
        'quantity'   => 1,
        'unit_price' => 100,
    ]);

    $response = $this->actingAs($buyer2)->postJson('/api/payments', [
        'order_id' => $order->id,
        'method'   => 'credit_card',
    ]);

    $response->assertStatus(403);
});


// ── GET /payments/{id} — Estado del pago ────────────────────

test('comprador puede ver el estado de su pago', function () {
    $buyer   = createBuyer();
    $seller  = createSeller();
    $product = createProductForSeller($seller, ['price' => 100]);

    $order = Order::factory()->create([
        'buyer_id' => $buyer->id,
        'status'   => 'paid',
        'total'    => 100,
    ]);
    $order->items()->create([
        'product_id' => $product->id,
        'quantity'   => 1,
        'unit_price' => 100,
    ]);

    $payment = Payment::create([
        'order_id'       => $order->id,
        'method'         => 'credit_card',
        'status'         => 'completed',
        'amount'         => 100,
        'transaction_id' => 'TXN-TEST123',
        'paid_at'        => now(),
    ]);

    $response = $this->actingAs($buyer)->getJson("/api/payments/{$payment->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $payment->id)
        ->assertJsonPath('data.status', 'completed');
});


// ── Flujo completo: crear pedido → pagar → confirmar ────────

test('flujo completo de compra: crear pedido, pagar y confirmar envío', function () {
    $buyer   = createBuyer();
    $seller  = createSeller();
    $product = createProductForSeller($seller, ['price' => 150.00, 'stock' => 10]);

    // Step 1: Create order
    $orderResponse = $this->actingAs($buyer)->postJson('/api/orders', [
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2],
        ],
    ]);

    $orderResponse->assertStatus(201);
    $orderId = $orderResponse->json('data.id');

    // Step 2: Pay
    $paymentResponse = $this->actingAs($buyer)->postJson('/api/payments', [
        'order_id' => $orderId,
        'method'   => 'credit_card',
    ]);

    $paymentResponse->assertStatus(201)
        ->assertJsonPath('data.status', 'completed');

    // Step 3: Seller ships
    $shipResponse = $this->actingAs($seller)->patchJson("/api/seller/orders/{$orderId}/status", [
        'status' => 'shipped',
    ]);

    $shipResponse->assertStatus(200)
        ->assertJsonPath('data.status', 'shipped');

    // Step 4: Seller marks as delivered
    $deliverResponse = $this->actingAs($seller)->patchJson("/api/seller/orders/{$orderId}/status", [
        'status' => 'delivered',
    ]);

    $deliverResponse->assertStatus(200)
        ->assertJsonPath('data.status', 'delivered');

    // Verify final state
    $order = Order::find($orderId);
    expect($order->status)->toBe('delivered');
    expect($order->total)->toBe('300.00');
    expect($product->fresh()->stock)->toBe(8);
});
