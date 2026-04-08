<?php
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    app('Spatie\Permission\PermissionRegistrar')->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'buyer',  'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'seller', 'guard_name' => 'web']);
});

// ── Buyer Profile ─────────────────────────────────────────────

test('comprador puede ver su perfil', function () {
    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');

    $response = $this->actingAs($buyer)->getJson('/api/buyer/profile');

    $response->assertStatus(200);
});

test('comprador puede actualizar su perfil', function () {
    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');

    $response = $this->actingAs($buyer)->putJson('/api/buyer/profile', [
        'address' => 'Calle 5, Col. Centro',
        'city'    => 'San Salvador',
        'country' => 'El Salvador',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('buyer_profiles', ['city' => 'San Salvador']);
});

test('vendedor no puede acceder al perfil de comprador', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $response = $this->actingAs($seller)->getJson('/api/buyer/profile');

    $response->assertStatus(403);
});

// ── Seller Profile ────────────────────────────────────────────

test('vendedor puede ver su perfil', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    SellerProfile::create([
        'user_id'    => $seller->id,
        'store_name' => 'Tienda Test',
    ]);

    $response = $this->actingAs($seller)->getJson('/api/seller/profile');

    $response->assertStatus(200)
        ->assertJsonPath('data.store_name', 'Tienda Test');
});

test('vendedor puede actualizar su perfil de tienda', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Viejo Nombre']);

    $response = $this->actingAs($seller)->putJson('/api/seller/profile', [
        'store_name'  => 'Nuevo Nombre',
        'description' => 'La mejor tienda del marketplace',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('seller_profiles', ['store_name' => 'Nuevo Nombre']);
});

test('comprador no puede acceder al perfil de vendedor', function () {
    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');

    $response = $this->actingAs($buyer)->getJson('/api/seller/profile');

    $response->assertStatus(403);
});