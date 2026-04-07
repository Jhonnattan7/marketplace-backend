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

    public function index(Request $request): OrderReturnCollection
    {
        Gate::authorize('viewAny', OrderReturn::class);

        $returns = OrderReturn::where('buyer_id', $request->user()->id)
            ->latest()
            ->paginate();

        return new OrderReturnCollection($returns);
    }

    public function store(StoreReturnRequest $request, ReturnService $service): JsonResponse
    {
        Gate::authorize('create', OrderReturn::class);

        $order = Order::findOrFail($request->order_id);

        try {
            $orderReturn = $service->submitRequest($order, $request->reason);
            
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

