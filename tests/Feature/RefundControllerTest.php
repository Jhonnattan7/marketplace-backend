<?php

use App\Models\OrderReturn;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'buyer']);
    $this->buyer = User::factory()->create();
    $this->buyer->assignRole('buyer');
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
