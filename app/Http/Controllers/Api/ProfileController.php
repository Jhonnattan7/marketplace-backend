<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BuyerProfile;
use App\Models\SellerProfile;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ApiResponse;

    // GET /buyer/profile
    public function showBuyerProfile(Request $request): JsonResponse
    {
        $this->authorize('viewBuyerProfile', $request->user());

        $profile = $request->user()->buyerProfile
            ?? BuyerProfile::create(['user_id' => $request->user()->id]);

        return $this->successResponse($profile);
    }

    // PUT /buyer/profile
    public function updateBuyerProfile(Request $request): JsonResponse
    {
        $this->authorize('updateBuyerProfile', $request->user());

        $validated = $request->validate([
            'address' => 'sometimes|string|max:255',
            'city'    => 'sometimes|string|max:100',
            'country' => 'sometimes|string|max:100',
        ]);

        $profile = $request->user()->buyerProfile
            ?? BuyerProfile::create(['user_id' => $request->user()->id]);

        $profile->update($validated);

        return $this->successResponse($profile, 'Perfil actualizado');
    }

    // GET /seller/profile
    public function showSellerProfile(Request $request): JsonResponse
    {
        $this->authorize('viewSellerProfile', $request->user());

        $profile = $request->user()->sellerProfile;

        if (!$profile) {
            return $this->errorResponse('Perfil de vendedor no encontrado', 404);
        }

        return $this->successResponse($profile);
    }

    // PUT /seller/profile
    public function updateSellerProfile(Request $request): JsonResponse
    {
        $this->authorize('updateSellerProfile', $request->user());

        $profile = $request->user()->sellerProfile;

        if (!$profile) {
            return $this->errorResponse('Perfil de vendedor no encontrado', 404);
        }

        $validated = $request->validate([
            'store_name'  => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
        ]);

        $profile->update($validated);

        return $this->successResponse($profile, 'Perfil de vendedor actualizado');
    }
}