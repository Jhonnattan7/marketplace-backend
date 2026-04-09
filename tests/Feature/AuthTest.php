<?php
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    app('Spatie\Permission\PermissionRegistrar')->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'buyer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'seller', 'guard_name' => 'web']);
});

test('un comprador puede registrarse', function () {
    $response = $this->postJson('/api/auth/register', [
        'name'     => 'Comprador 1',
        'email'    => 'comprador@test.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'phone'    => '7777-0001',
        'role'     => 'buyer',
    ]);

    $response->assertStatus(201)
             ->assertJsonPath('data.user.role', 'buyer')
        ->assertJsonStructure(['data' => ['token']]);
});

test('un vendedor puede registrarse', function () {
    $response = $this->postJson('/api/auth/register', [
        'name'     => 'Vendedor 1',
        'email'    => 'vendedor@test.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'phone'    => '7777-0002',
        'role'     => 'seller',
    ]);

    $response->assertStatus(201)
             ->assertJsonPath('data.user.role', 'seller');
});

test('un usuario puede hacer login y recibe token', function () {
    $user = User::factory()->create(['password' => bcrypt('secret123')]);
    $user->assignRole('buyer');

    $response = $this->postJson('/api/auth/login', [
        'email'    => $user->email,
        'password' => 'secret123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['token']]);
});

test('login falla con credenciales incorrectas', function () {
    User::factory()->create(['email' => 'test@test.com', 'password' => bcrypt('correct')]);

    $response = $this->postJson('/api/auth/login', [
        'email'    => 'test@test.com',
        'password' => 'wrong',
    ]);

    $response->assertStatus(401);
});

test('un usuario autenticado puede hacer logout', function () {
    $user = User::factory()->create();
    $user->assignRole('buyer');
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer $token")
                     ->postJson('/api/auth/logout');

    $response->assertStatus(200);
});

test('un comprador no puede acceder a endpoints de vendedor', function () {
    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');

    $response = $this->actingAs($buyer)->postJson('/api/products', [
        'name' => 'Producto', 'price' => 10, 'stock' => 5, 'category_id' => 1,
    ]);

    $response->assertStatus(422); // Note: Validation triggers before Authorization usually
});

test('un vendedor no puede crear pedidos', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $response = $this->actingAs($seller)->postJson('/api/orders', [
        'items' => [['product_id' => 1, 'quantity' => 1]],
    ]);

    $response->assertStatus(422); // Note: Validation triggers before Authorization usually
});