<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'buyer_id'   => $this->buyer_id,
            'status'     => $this->status,
            'total'      => $this->total,
            'notes'      => $this->notes,
            'buyer'      => $this->when($this->relationLoaded('buyer'), fn() => [
                'id'    => $this->buyer->id,
                'name'  => $this->buyer->name,
                'email' => $this->buyer->email,
            ]),
            'items'      => OrderItemResource::collection($this->whenLoaded('items')),
            'payment'    => $this->when($this->relationLoaded('payment') && $this->payment, fn() => [
                'id'             => $this->payment->id,
                'method'         => $this->payment->method,
                'status'         => $this->payment->status,
                'amount'         => $this->payment->amount,
                'transaction_id' => $this->payment->transaction_id,
                'paid_at'        => $this->payment->paid_at?->toDateTimeString(),
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
