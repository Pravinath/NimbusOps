<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UseSparePartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(
            $this->user()?->role,
            ['technician', 'admin'],
            true
        );
    }

    public function rules(): array
    {
        return [
            'spare_part_id' => [
                'required',
                'integer',
                'exists:spare_parts,id',
            ],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}