<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RefundResource;
use App\Models\Refund;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class RefundController extends Controller
{
    use ApiResponse;

    public function show(Refund $refund): JsonResponse
    {
        $refund->load('orderReturn');
        
        Gate::authorize('view', clone $refund->orderReturn);

        return $this->successResponse(new RefundResource($refund));
    }
}

