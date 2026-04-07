<?php

namespace App\Services;

use App\Events\ReturnRequested;
use App\Exceptions\ReturnNotEligibleException;
use App\Models\Order;
use App\Models\OrderReturn;

class ReturnService
{
    public function __construct(protected ReturnEligibilityService $eligibilityService)
    {
    }

    /**
     * Submit a return request for a given order by a user
     * 
     * @throws ReturnNotEligibleException
     */
    public function submitRequest(Order $order, string $reason): OrderReturn
    {
        if (!$this->eligibilityService->isEligible($order)) {
            throw new ReturnNotEligibleException();
        }

        $orderReturn = OrderReturn::create([
            'order_id' => $order->id,
            'buyer_id' => $order->buyer_id,
            'reason' => $reason,
            'status' => 'pending',
        ]);

        ReturnRequested::dispatch($orderReturn);

        return $orderReturn;
    }
}
