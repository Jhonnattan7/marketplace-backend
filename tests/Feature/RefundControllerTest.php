<?php

use App\Models\BuyerProfile;
use App\Models\OrderReturn;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    app('Spatie\\Permission\\PermissionRegistrar')->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'view-own-orders', 'guard_name' => 'web']);
    
    $buyerRole = Role::firstOrCreate(['name' => 'buyer', 'guard_name' => 'web']);
    $buyerRole->givePermissionTo('view-own-orders');

    $this->buyer = User::factory()->create();
    $this->buyer->assignRole('buyer');
    $this->buyerProfile = BuyerProfile::factory()->create(['user_id' => $this->buyer->id]);
    $this->actingAs($this->buyer);
});

it('can view own refund details', function () {
    $orderReturn = OrderReturn::factory()->create([
        'buyer_id' => $this->buyer->id,
        'status' => 'completed'
    ]);
    
    $refund = Refund::factory()->create([
        'order_return_id' => $orderReturn->id,
        'amount' => 100.50,
        'status' => 'processed'
    ]);
    
    $response = $this->getJson("/api/refunds/{$refund->id}");
    
    $response->assertOk()
        ->assertJsonPath('data.id', $refund->id)
        ->assertJsonPath('data.amount', 100.50)
        ->assertJsonPath('data.status', 'processed');
});

it('cannot view other buyers refund details', function () {
    $otherBuyer = User::factory()->create();
    
    $orderReturn = OrderReturn::factory()->create([
        'buyer_id' => $otherBuyer->id,
        'status' => 'completed'
    ]);
    
    $refund = Refund::factory()->create([
        'order_return_id' => $orderReturn->id,
    ]);
    
    $response = $this->getJson("/api/refunds/{$refund->id}");
    
    $response->assertForbidden();
});
