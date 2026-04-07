<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via Gate & Policy
    }

    public function rules(): array
    {
        return [
            'notes'              => ['nullable', 'string', 'max:1000'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'              => 'Debes incluir al menos un producto.',
            'items.min'                    => 'Debes incluir al menos un producto.',
            'items.*.product_id.required'  => 'El ID del producto es obligatorio.',
            'items.*.product_id.exists'    => 'El producto :input no fue encontrado.',
            'items.*.quantity.required'    => 'La cantidad es obligatoria.',
            'items.*.quantity.min'         => 'La cantidad mínima es 1.',
        ];
    }
}
