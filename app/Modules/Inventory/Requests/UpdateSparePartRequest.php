<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSparePartRequest extends FormRequest
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
            'sku' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('spare_parts', 'sku')
                    ->ignore($this->route('sparePart')),
            ],
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'reorder_level' => ['sometimes', 'integer', 'min:0'],
            'unit_cost' => ['sometimes', 'numeric', 'min:0'],
            'status' => [
                'sometimes',
                Rule::in(['active', 'inactive']),
            ],
        ];
    }
}
