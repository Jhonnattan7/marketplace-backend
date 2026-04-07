<?php

namespace App\Services;

use App\Models\Order;

class ReturnEligibilityService
{
    /**
     * Determines if an order is eligible for to be returned.
     */
    public function isEligible(Order $order): bool
    {
        return $order->status === 'delivered';
    }
}
