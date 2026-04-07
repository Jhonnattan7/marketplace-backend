<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'method'   => ['required', 'string', 'in:credit_card,paypal,transfer,cash'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'El ID del pedido es obligatorio.',
            'order_id.exists'   => 'El pedido no fue encontrado.',
            'method.required'   => 'El método de pago es obligatorio.',
            'method.in'         => 'Método de pago no válido. Opciones: credit_card, paypal, transfer, cash.',
        ];
    }
}
