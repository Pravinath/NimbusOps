<?php

namespace App\Modules\WorkOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddWorkOrderUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'notes' => ['required', 'string', 'max:2000'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}