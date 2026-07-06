<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(
            $this->user()?->role,
            ['inventory', 'admin'],
            true
        );
    }

    public function rules(): array
    {
        return [
            'operation' => [
                'required',
                Rule::in(['increase', 'decrease']),
            ],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['required', 'string', 'max:2000'],
        ];
    }
}