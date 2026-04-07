<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Post(
     *   path="/payments",
     *   summary="Procesar pago de un pedido",
     *   description="Crea un registro de pago asociado a un pedido pendiente. Simula el procesamiento y actualiza el estado del pedido a 'paid'.",
     *   tags={"Pagos"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"order_id","method"},
     *       @OA\Property(property="order_id", type="integer", example=1),
     *       @OA\Property(property="method", type="string", enum={"credit_card","paypal","transfer","cash"}, example="credit_card")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Pago procesado exitosamente"),
     *   @OA\Response(response=403, description="No autorizado"),
     *   @OA\Response(response=422, description="Error de validación o pedido ya pagado")
     * )
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $order = Order::findOrFail($validated['order_id']);

        // Only the buyer of the order can pay
        if ($order->buyer_id !== $request->user()->id) {
            return $this->errorResponse('No puedes pagar un pedido que no es tuyo.', 403);
        }

        if ($order->status !== 'pending') {
            return $this->errorResponse('Este pedido ya fue procesado o cancelado.', 422);
        }

        if ($order->payment()->exists()) {
            return $this->errorResponse('Este pedido ya tiene un pago asociado.', 422);
        }

        return DB::transaction(function () use ($order, $validated) {
            $transactionId = 'TXN-' . strtoupper(Str::random(16));

            $payment = Payment::create([
                'order_id'       => $order->id,
                'method'         => $validated['method'],
                'status'         => 'completed',
                'amount'         => $order->total,
                'transaction_id' => $transactionId,
                'paid_at'        => now(),
            ]);

            $order->update(['status' => 'paid']);

            $payment->load('order');

            return $this->successResponse(
                new PaymentResource($payment),
                'Pago procesado exitosamente',
                201
            );
        });
    }

    /**
     * @OA\Get(
     *   path="/payments/{id}",
     *   summary="Estado del pago",
     *   description="Consulta el estado y detalle de un pago específico.",
     *   tags={"Pagos"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, schema={"type":"integer"}),
     *   @OA\Response(response=200, description="Detalle del pago"),
     *   @OA\Response(response=403, description="No autorizado"),
     *   @OA\Response(response=404, description="Pago no encontrado")
     * )
     */
    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        $payment->load('order.items.product');

        return $this->successResponse(new PaymentResource($payment));
    }
}
