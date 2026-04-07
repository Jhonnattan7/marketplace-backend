<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'order_id'       => $this->order_id,
            'method'         => $this->method,
            'status'         => $this->status,
            'amount'         => $this->amount,
            'transaction_id' => $this->transaction_id,
            'paid_at'        => $this->paid_at?->toDateTimeString(),
            'order'          => $this->when($this->relationLoaded('order'), fn() => [
                'id'     => $this->order->id,
                'status' => $this->order->status,
                'total'  => $this->order->total,
            ]),
            'created_at'     => $this->created_at?->toDateTimeString(),
            'updated_at'     => $this->updated_at?->toDateTimeString(),
        ];
    }
}
