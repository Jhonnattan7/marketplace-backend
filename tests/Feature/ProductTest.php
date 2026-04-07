<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    app('Spatie\\Permission\\PermissionRegistrar')->forgetCachedPermissions();
    $roleModel = config('permission.models.role');

    foreach (['admin', 'seller', 'buyer', 'vendedor', 'comprador'] as $role) {
        $roleModel::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
});

function makeUserWithRole(string $role): User
{
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('cualquiera puede listar productos activos', function () {
    Product::factory()->count(3)->create(['status' => 'active']);

    $response = $this->getJson('/api/products');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [['id', 'name', 'price', 'stock', 'status', 'category', 'seller']],
        ]);
});

test('cualquiera puede ver el detalle de un producto', function () {
    $product = Product::factory()->create();

    $response = $this->getJson("/api/products/{$product->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $product->id);
});

test('vendedor autenticado puede crear un producto', function () {
    $category = Category::factory()->create();
    $seller = makeUserWithRole('seller');
    SellerProfile::factory()->create(['user_id' => $seller->id]);

    $response = $this->actingAs($seller)->postJson('/api/products', [
        'name' => 'Laptop Gamer',
        'description' => 'Una laptop muy potente',
        'price' => 999.99,
        'stock' => 5,
        'category_id' => $category->id,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('name', 'Laptop Gamer')
        ->assertJsonPath('status', 'active');
});

test('vendedor puede editar su propio producto', function () {
    $seller = makeUserWithRole('seller');
    $profile = SellerProfile::factory()->create(['user_id' => $seller->id]);
    $product = Product::factory()->create(['seller_profile_id' => $profile->id]);

    $response = $this->actingAs($seller)->putJson("/api/products/{$product->id}", [
        'name' => 'Nombre Actualizado',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'Nombre Actualizado');
});

test('vendedor puede eliminar su propio producto', function () {
    $seller = makeUserWithRole('seller');
    $profile = SellerProfile::factory()->create(['user_id' => $seller->id]);
    $product = Product::factory()->create(['seller_profile_id' => $profile->id]);

    $response = $this->actingAs($seller)->deleteJson("/api/products/{$product->id}");

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Producto eliminado correctamente.');

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});

test('vendedor ve solo sus propios productos', function () {
    $seller = makeUserWithRole('seller');
    $profile = SellerProfile::factory()->create(['user_id' => $seller->id]);

    Product::factory()->count(2)->create(['seller_profile_id' => $profile->id]);
    Product::factory()->count(3)->create();

    $response = $this->actingAs($seller)->getJson('/api/seller/products');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

test('comprador no puede crear un producto', function () {
    $category = Category::factory()->create();
    $buyer = makeUserWithRole('buyer');

    $response = $this->actingAs($buyer)->postJson('/api/products', [
        'name' => 'Intento fallido',
        'price' => 10.00,
        'stock' => 1,
        'category_id' => $category->id,
    ]);

    $response->assertStatus(403);
});

test('comprador no puede editar un producto', function () {
    $seller = makeUserWithRole('seller');
    $profile = SellerProfile::factory()->create(['user_id' => $seller->id]);
    $product = Product::factory()->create(['seller_profile_id' => $profile->id]);

    $buyer = makeUserWithRole('buyer');

    $response = $this->actingAs($buyer)->putJson("/api/products/{$product->id}", [
        'name' => 'Intento comprador',
    ]);

    $response->assertStatus(403);
});

test('vendedor no puede editar producto de otro vendedor', function () {
    $seller1 = makeUserWithRole('seller');
    $profile1 = SellerProfile::factory()->create(['user_id' => $seller1->id]);
    $product = Product::factory()->create(['seller_profile_id' => $profile1->id]);

    $seller2 = makeUserWithRole('seller');
    SellerProfile::factory()->create(['user_id' => $seller2->id]);

    $response = $this->actingAs($seller2)->putJson("/api/products/{$product->id}", [
        'name' => 'Intento de hackeo',
    ]);

    $response->assertStatus(403);
});

test('vendedor no puede eliminar producto de otro vendedor', function () {
    $seller1 = makeUserWithRole('seller');
    $profile1 = SellerProfile::factory()->create(['user_id' => $seller1->id]);
    $product = Product::factory()->create(['seller_profile_id' => $profile1->id]);

    $seller2 = makeUserWithRole('seller');
    SellerProfile::factory()->create(['user_id' => $seller2->id]);

    $response = $this->actingAs($seller2)->deleteJson("/api/products/{$product->id}");

    $response->assertStatus(403);
});

test('crear producto sin datos requeridos retorna 422', function () {
    $seller = makeUserWithRole('seller');
    SellerProfile::factory()->create(['user_id' => $seller->id]);

    $response = $this->actingAs($seller)->postJson('/api/products', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'price', 'stock', 'category_id']);
});

test('usuario no autenticado no puede crear producto', function () {
    $category = Category::factory()->create();

    $response = $this->postJson('/api/products', [
        'name' => 'Sin token',
        'price' => 10,
        'stock' => 1,
        'category_id' => $category->id,
    ]);

    $response->assertStatus(401);
});