<?php

use App\Models\Order;
use App\Services\ReturnEligibilityService;

it('determines an order is eligible if status is delivered', function () {
    $service = new ReturnEligibilityService();
    $order = new Order(['status' => 'delivered']);
    
    expect($service->isEligible($order))->toBeTrue();
});

it('determines an order is not eligible if status is not delivered', function (string $status) {
    $service = new ReturnEligibilityService();
    $order = new Order(['status' => $status]);
    
    expect($service->isEligible($order))->toBeFalse();
})->with([
    'pending',
    'paid',
    'shipped',
    'cancelled',
]);
