<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $products = Product::with(['category', 'sellerProfile'])
            ->where('status', 'active')
            ->paginate(15);

        return ProductResource::collection($products);
    }

    public function show(Product $product): ProductResource
    {
        $product->load(['category', 'sellerProfile']);

        return new ProductResource($product);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        Gate::authorize('is-seller');
        $this->authorize('create', Product::class);

        $sellerProfile = $request->user()->sellerProfile;

        if (!$sellerProfile) {
            return response()->json([
                'message' => 'Debes tener un perfil de vendedor para publicar productos.',
            ], 403);
        }

        $product = Product::create([
            ...$request->validated(),
            'seller_profile_id' => $sellerProfile->id,
            'seller_id' => $sellerProfile->user_id,
            'status' => 'active',
        ]);

        $product->load(['category', 'sellerProfile']);

        return response()->json(new ProductResource($product), 201);
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $this->authorize('update', $product);

        $product->update($request->validated());
        $product->load(['category', 'sellerProfile']);

        return new ProductResource($product);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return response()->json([
            'message' => 'Producto eliminado correctamente.',
        ]);
    }

    public function myProducts(Request $request): AnonymousResourceCollection
    {
        $sellerProfile = $request->user()->sellerProfile;

        if (!$sellerProfile) {
            abort(403, 'No tienes un perfil de vendedor.');
        }

        $products = Product::with(['category', 'sellerProfile'])
            ->where('seller_profile_id', $sellerProfile->id)
            ->paginate(15);

        return ProductResource::collection($products);
    }
}