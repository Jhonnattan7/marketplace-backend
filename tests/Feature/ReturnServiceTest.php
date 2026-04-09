<?php

use App\Events\ReturnRequested;
use App\Exceptions\ReturnNotEligibleException;
use App\Models\Order;
use App\Models\User;
use App\Services\ReturnEligibilityService;
use App\Services\ReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('creates a return successfully if eligible and dispatches event', function () {
    Event::fake();

    $user = User::factory()->create();
    $order = Order::factory()->create(['buyer_id' => $user->id, 'status' => 'delivered']);
    $reason = 'Item is broken.';

    $service = new ReturnService(new ReturnEligibilityService());
    $orderReturn = $service->submitRequest($order, $reason, $user->id);

    expect($orderReturn->id)->not->toBeNull()
        ->and($orderReturn->order_id)->toBe($order->id)
        ->and($orderReturn->buyer_id)->toBe($user->id)
        ->and($orderReturn->reason)->toBe($reason)
        ->and($orderReturn->status)->toBe('pending');
        
    $this->assertDatabaseHas('order_returns', [
        'id' => $orderReturn->id,
        'order_id' => $order->id,
        'buyer_id' => $user->id,
        'reason' => $reason,
        'status' => 'pending',
    ]);

    Event::assertDispatched(ReturnRequested::class);
});

it('throws exception and does not create return if not eligible', function () {
    Event::fake();

    $user = User::factory()->create();
    $order = Order::factory()->create(['buyer_id' => $user->id, 'status' => 'shipped']);
    $reason = 'Item is broken.';

    $service = new ReturnService(new ReturnEligibilityService());
    
    $service->submitRequest($order, $reason);
})->throws(ReturnNotEligibleException::class, 'Order is not eligible for return.');


