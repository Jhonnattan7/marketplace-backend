<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateReturnStatusRequest;
use App\Http\Resources\OrderReturnCollection;
use App\Http\Resources\OrderReturnResource;
use App\Models\OrderReturn;
use App\Services\RefundService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminReturnController extends Controller
{
    use ApiResponse;

    public function index(Request $request): OrderReturnCollection
    {
        Gate::authorize('viewAnyAdmin', OrderReturn::class);

        $returns = OrderReturn::latest()->paginate();

        return new OrderReturnCollection($returns);
    }

    public function show($id): JsonResponse
    {
        $return = OrderReturn::findOrFail($id);
        
        Gate::authorize('viewAdmin', $return);

        return $this->successResponse(new OrderReturnResource($return));
    }

    public function updateStatus(UpdateReturnStatusRequest $request, $id, RefundService $refundService): JsonResponse
    {
        $return = OrderReturn::findOrFail($id);

        Gate::authorize('updateAdmin', $return);
        
        if ($return->status !== 'pending') {
            return $this->errorResponse('Return request has already been processed', 422);
        }

        if ($request->status === 'approved') {
            // Approval flow updates status to approved inside approve(), then runs refund process
            $return->approve();
            $refundService->processRefund($return);
            
        } elseif ($request->status === 'rejected') {
            // Rejection just sets notes and status
            $return->reject($request->admin_notes);
        }

        return $this->successResponse(
            new OrderReturnResource($return->fresh()),
            "Return request marked as {$request->status} successfully."
        );
    }
}

