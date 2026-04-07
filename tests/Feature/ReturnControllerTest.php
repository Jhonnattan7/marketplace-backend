<?php

use App\Models\Order;
use App\Models\OrderReturn;
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

it('prevents non-buyers from accessing returns', function() {
    $otherUser = User::factory()->create();
    $this->actingAs($otherUser);
    
    $response = $this->getJson('/api/returns');
    $response->assertForbidden();
});

it('can list buyer returns', function () {
    OrderReturn::factory()->count(3)->create(['buyer_id' => $this->buyer->id]);
    
    $response = $this->getJson('/api/returns');
    
    $response->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'order_id', 'status', 'reason']]]);
});

it('can create a return request', function () {
    $order = Order::factory()->create(['buyer_id' => $this->buyer->id, 'status' => 'delivered']);
    
    $response = $this->postJson('/api/returns', [
        'order_id' => $order->id,
        'reason' => 'This product arrived damaged and has a crack on the screen.'
    ]);
    
    $response->assertCreated()
        ->assertJsonPath('data.status', 'pending');
        
    $this->assertDatabaseHas('order_returns', [
        'order_id' => $order->id,
        'status' => 'pending'
    ]);
});

