<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via Gate
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:paid,shipped,delivered,cancelled'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'El estado es obligatorio.',
            'status.in'       => 'El estado debe ser: paid, shipped, delivered o cancelled.',
        ];
    }
}
