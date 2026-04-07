<?php

use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    Permission::firstOrCreate(['name' => 'manage-returns']);
    $this->admin->givePermissionTo('manage-returns');
    
    $this->actingAs($this->admin);
});

it('lists all returns for admin', function () {
    OrderReturn::factory()->count(2)->create(['status' => 'pending']);
    
    $response = $this->getJson('/api/admin/returns');
    
    $response->assertOk()
             ->assertJsonCount(2, 'data');
});

it('can reject a return with notes', function () {
    $orderReturn = OrderReturn::factory()->create(['status' => 'pending']);
    
    $response = $this->putJson("/api/admin/returns/{$orderReturn->id}/status", [
        'status' => 'rejected',
        'admin_notes' => 'Product does not qualify according to policy.'
    ]);
    
    $response->assertOk()
             ->assertJsonPath('data.status', 'rejected')
             ->assertJsonPath('data.admin_notes', 'Product does not qualify according to policy.');
             
    $this->assertDatabaseHas('order_returns', [
        'id' => $orderReturn->id,
        'status' => 'rejected'
    ]);
});

