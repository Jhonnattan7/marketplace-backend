<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'product_id' => $this->product_id,
            'quantity'   => $this->quantity,
            'unit_price' => $this->unit_price,
            'subtotal'   => $this->subtotal,
            'product'    => $this->when($this->relationLoaded('product'), fn() => [
                'id'   => $this->product->id,
                'name' => $this->product->name,
                'price' => $this->product->price,
            ]),
        ];
    }
}
