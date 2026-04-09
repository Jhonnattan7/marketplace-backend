<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Post(
     *   path="/orders",
     *   summary="Crear un nuevo pedido",
     *   description="Permite a un comprador crear un pedido con uno o más productos. Valida stock disponible y calcula los totales automáticamente.",
     *   tags={"Pedidos"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"items"},
     *       @OA\Property(property="notes", type="string", nullable=true, example="Entregar en la puerta principal"),
     *       @OA\Property(property="items", type="array",
     *         @OA\Items(
     *           required={"product_id","quantity"},
     *           @OA\Property(property="product_id", type="integer", example=1),
     *           @OA\Property(property="quantity", type="integer", minimum=1, example=2)
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(response=201, description="Pedido creado exitosamente"),
     *   @OA\Response(response=403, description="Solo compradores pueden crear pedidos"),
     *   @OA\Response(response=422, description="Error de validación o stock insuficiente")
     * )
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $this->authorize('create', Order::class);

        $validated = $request->validated();

        return DB::transaction(function () use ($validated, $request) {
            $total = 0;
            $orderItems = [];

            foreach ($validated['items'] as $item) {
                $product = Product::where('id', $item['product_id'])
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    abort(422, "El producto #{$item['product_id']} no está disponible.");
                }

                if ($product->stock < $item['quantity']) {
                    abort(422, "Stock insuficiente para '{$product->name}'. Disponible: {$product->stock}.");
                }

                $unitPrice = $product->price;
                $subtotal = $unitPrice * $item['quantity'];
                $total += $subtotal;

                $product->decrement('stock', $item['quantity']);

                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                ];
            }

            $buyerProfileId = $request->user()->buyerProfile?->id;
            if (!$buyerProfileId) {
                abort(403, "No tienes perfil de comprador activo.");
            }

            $order = Order::create([
                'buyer_id' => $buyerProfileId,
                'status' => 'pending',
                'total' => $total,
                'notes' => $validated['notes'] ?? null,
            ]);

            $order->items()->createMany($orderItems);

            $order->load(['items.product', 'buyer']);

            return $this->successResponse(
                new OrderResource($order),
                'Pedido creado exitosamente',
                201
            );
        });
    }

    /**
     * @OA\Get(
     *   path="/orders",
     *   summary="Listar mis pedidos (comprador)",
     *   description="Devuelve los pedidos del comprador autenticado, con paginación.",
     *   tags={"Pedidos"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="status", in="query", required=false, schema={"type":"string","enum":{"pending","paid","shipped","delivered","cancelled"}}),
     *   @OA\Response(response=200, description="Lista de pedidos del comprador")
     * )
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $buyerProfileId = $request->user()->buyerProfile?->id;

        $query = Order::with(['items.product', 'payment'])
            ->where('buyer_id', $buyerProfileId)
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $this->successResponse(OrderResource::collection($query->paginate(15)));
    }

    /**
     * @OA\Get(
     *   path="/orders/{id}",
     *   summary="Detalle de pedido",
     *   description="Muestra el detalle completo de un pedido (comprador ve los suyos).",
     *   tags={"Pedidos"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, schema={"type":"integer"}),
     *   @OA\Response(response=200, description="Detalle del pedido"),
     *   @OA\Response(response=403, description="No autorizado"),
     *   @OA\Response(response=404, description="Pedido no encontrado")
     * )
     */
    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->load(['items.product', 'payment', 'buyer']);

        return $this->successResponse(new OrderResource($order));
    }

    /**
     * @OA\Get(
     *   path="/seller/orders",
     *   summary="Pedidos recibidos (vendedor)",
     *   description="Lista los pedidos que contienen productos del vendedor autenticado.",
     *   tags={"Pedidos"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="status", in="query", required=false, schema={"type":"string"}),
     *   @OA\Response(response=200, description="Lista de pedidos del vendedor"),
     *   @OA\Response(response=403, description="No autorizado")
     * )
     */
    public function sellerOrders(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $sellerProfileId = $request->user()->sellerProfile?->id;

        $query = Order::with(['items.product', 'payment', 'buyer'])
            ->whereHas('items.product', function ($q) use ($sellerProfileId) {
                $q->where('seller_profile_id', $sellerProfileId);
            })
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $this->successResponse(OrderResource::collection($query->paginate(15)));
    }

    /**
     * @OA\Put(
     *   path="/seller/orders/{id}/status",
     *   summary="Actualizar estado del pedido (vendedor)",
     *   description="Permite al vendedor actualizar el estado de un pedido que contenga sus productos (e.g. shipped, delivered).",
     *   tags={"Pedidos"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, schema={"type":"integer"}),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"status"},
     *       @OA\Property(property="status", type="string", enum={"paid","shipped","delivered","cancelled"}, example="shipped")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Estado actualizado"),
     *   @OA\Response(response=403, description="No autorizado"),
     *   @OA\Response(response=422, description="Transición de estado no válida")
     * )
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $this->authorize('updateStatus', $order);

        // Verify seller owns at least one product in the order
        $sellerProfileId = $request->user()->sellerProfile?->id;
        $hasProducts = $order->items()
            ->whereHas('product', fn($q) => $q->where('seller_profile_id', $sellerProfileId))
            ->exists();

        if (!$hasProducts) {
            return $this->errorResponse('No tienes productos en este pedido.', 403);
        }

        $newStatus = $request->validated()['status'];

        // Validate status transitions
        $allowedTransitions = [
            'pending'   => ['paid', 'cancelled'],
            'paid'      => ['shipped', 'cancelled'],
            'shipped'   => ['delivered'],
            'delivered' => [],
            'cancelled' => [],
        ];

        $currentStatus = $order->status;

        if (!in_array($newStatus, $allowedTransitions[$currentStatus] ?? [])) {
            return $this->errorResponse(
                "No se puede cambiar de '{$currentStatus}' a '{$newStatus}'.",
                422
            );
        }

        // If cancelling, restore stock
        if ($newStatus === 'cancelled') {
            foreach ($order->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }
        }

        $order->update(['status' => $newStatus]);
        $order->load(['items.product', 'payment', 'buyer']);

        return $this->successResponse(
            new OrderResource($order),
            'Estado del pedido actualizado'
        );
    }
}
