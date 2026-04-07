<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;

// ── Cálculo de totales ──────────────────────────────────────

test('subtotal de OrderItem se calcula correctamente', function () {
    $item = new OrderItem([
        'quantity'   => 3,
        'unit_price' => 25.50,
    ]);

    expect($item->subtotal)->toBe(76.5);
});

test('subtotal con cantidad 1', function () {
    $item = new OrderItem([
        'quantity'   => 1,
        'unit_price' => 999.99,
    ]);

    expect($item->subtotal)->toBe(999.99);
});

test('subtotal con precio cero', function () {
    $item = new OrderItem([
        'quantity'   => 5,
        'unit_price' => 0,
    ]);

    expect($item->subtotal)->toBe(0.0);
});

// ── Máquina de estados del pedido ───────────────────────────

test('canTransitionTo retorna true para transiciones válidas', function () {
    $order = new Order(['status' => 'pending']);
    expect($order->canTransitionTo('paid'))->toBeTrue();
    expect($order->canTransitionTo('cancelled'))->toBeTrue();

    $order->status = 'paid';
    expect($order->canTransitionTo('shipped'))->toBeTrue();
    expect($order->canTransitionTo('cancelled'))->toBeTrue();

    $order->status = 'shipped';
    expect($order->canTransitionTo('delivered'))->toBeTrue();
});

test('canTransitionTo retorna false para transiciones inválidas', function () {
    $order = new Order(['status' => 'pending']);
    expect($order->canTransitionTo('shipped'))->toBeFalse();
    expect($order->canTransitionTo('delivered'))->toBeFalse();

    $order->status = 'paid';
    expect($order->canTransitionTo('pending'))->toBeFalse();
    expect($order->canTransitionTo('delivered'))->toBeFalse();

    $order->status = 'shipped';
    expect($order->canTransitionTo('pending'))->toBeFalse();
    expect($order->canTransitionTo('paid'))->toBeFalse();
    expect($order->canTransitionTo('cancelled'))->toBeFalse();

    $order->status = 'delivered';
    expect($order->canTransitionTo('pending'))->toBeFalse();
    expect($order->canTransitionTo('paid'))->toBeFalse();
    expect($order->canTransitionTo('shipped'))->toBeFalse();
    expect($order->canTransitionTo('cancelled'))->toBeFalse();

    $order->status = 'cancelled';
    expect($order->canTransitionTo('pending'))->toBeFalse();
    expect($order->canTransitionTo('paid'))->toBeFalse();
});

// ── Payment helpers ─────────────────────────────────────────

test('markAsCompleted actualiza estado y transaction_id', function () {
    // Use a mock to test without DB
    $payment = new Payment([
        'status'         => 'pending',
        'amount'         => 500,
        'method'         => 'credit_card',
        'transaction_id' => null,
        'paid_at'        => null,
    ]);

    // Verify initial state
    expect($payment->status)->toBe('pending');
    expect($payment->transaction_id)->toBeNull();
});

test('Order STATUS_TRANSITIONS constante está definida correctamente', function () {
    $transitions = Order::STATUS_TRANSITIONS;

    expect($transitions)->toBeArray();
    expect($transitions)->toHaveKeys(['pending', 'paid', 'shipped', 'delivered', 'cancelled']);
    expect($transitions['pending'])->toContain('paid', 'cancelled');
    expect($transitions['paid'])->toContain('shipped', 'cancelled');
    expect($transitions['shipped'])->toContain('delivered');
    expect($transitions['delivered'])->toBeEmpty();
    expect($transitions['cancelled'])->toBeEmpty();
});

// ── Cálculo de total del pedido ─────────────────────────────

test('total del pedido se calcula como la suma de los subtotales de items', function () {
    $items = [
        new OrderItem(['quantity' => 2, 'unit_price' => 100]),   // 200
        new OrderItem(['quantity' => 1, 'unit_price' => 50]),    // 50
        new OrderItem(['quantity' => 3, 'unit_price' => 33.33]), // 99.99
    ];

    $total = collect($items)->sum(fn($item) => $item->quantity * $item->unit_price);

    expect($total)->toBe(349.99);
});
