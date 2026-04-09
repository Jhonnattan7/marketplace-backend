<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('processes a refund, updates payment status, and restores product stock', function () {
    $user = User::factory()->create();
    $buyerProfile = \App\Models\BuyerProfile::factory()->create(['user_id' => $user->id]);
    
    // Setup Order, Payment and its Items
    $product1 = Product::factory()->create(['stock' => 10, 'price' => 50.00]);
    $product2 = Product::factory()->create(['stock' => 5, 'price' => 20.00]);
    
    $order = Order::factory()->create(['buyer_id' => $buyerProfile->id, 'status' => 'delivered', 'total' => 120.00]);
    $orderItem1 = new OrderItem(['product_id' => $product1->id, 'quantity' => 2, 'unit_price' => 50.00]);
    $orderItem1->order_id = $order->id;
    $orderItem1->save();
    
    $orderItem2 = new OrderItem(['product_id' => $product2->id, 'quantity' => 1, 'unit_price' => 20.00]);
    $orderItem2->order_id = $order->id;
    $orderItem2->save();
    
    // Create payment using Eloquent to respect foreign keys
    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => 120.00,
        'status' => 'completed',
        'method' => 'card', // or whatever is valid
    ]);
    
    
    // Create an approved return
    $orderReturn = OrderReturn::create([
        'order_id' => $order->id,
        'buyer_id' => $user->id,
        'reason' => 'Defective items',
        'status' => 'approved',
        'resolved_at' => now(),
    ]);

    // Service under test
    $service = new RefundService();
    $refund = $service->processRefund($orderReturn);

    // Assert: Refund is created correctly
    expect($refund->id)->not->toBeNull()
        ->and($refund->order_return_id)->toBe($orderReturn->id)
        ->and($refund->payment_id)->toBe($payment->id)
        ->and((float) $refund->amount)->toBe(120.00)
        ->and($refund->status)->toBe('processed');

    // Assert: DB tables updated correctly
    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => 'refunded'
    ]);
    
    $this->assertDatabaseHas('order_returns', [
        'id' => $orderReturn->id,
        'status' => 'completed'
    ]);

    // Assert: Stock is restored
    expect($product1->fresh()->stock)->toBe(12);
    expect($product2->fresh()->stock)->toBe(6);
});

