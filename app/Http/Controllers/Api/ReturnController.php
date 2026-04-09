<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ReturnNotEligibleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReturnRequest;
use App\Http\Resources\OrderReturnCollection;
use App\Http\Resources\OrderReturnResource;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Services\ReturnService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReturnController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        Gate::authorize('viewAny', OrderReturn::class);

        $buyerProfileId = $request->user()->buyerProfile?->id;

        $returns = OrderReturn::whereHas('order', function ($query) use ($buyerProfileId) {
            $query->where('buyer_id', $buyerProfileId);
        })
            ->latest()
            ->paginate();

        return $this->successResponse(new OrderReturnCollection($returns));
    }

    public function store(StoreReturnRequest $request, ReturnService $service): JsonResponse
    {
        Gate::authorize('create', OrderReturn::class);

        $order = Order::findOrFail($request->order_id);
        $buyerId = $request->user()->buyerProfile->id;

        try {
            $orderReturn = $service->submitRequest($order, $request->reason, $buyerId);
            
            return $this->successResponse(
                new OrderReturnResource($orderReturn),
                'Return request submitted successfully.',
                201
            );
        } catch (ReturnNotEligibleException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function show(OrderReturn $return): JsonResponse
    {
        Gate::authorize('view', $return);

        return $this->successResponse(new OrderReturnResource($return));
    }
}

