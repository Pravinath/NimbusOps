<?php

namespace App\Modules\WorkOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'resolution_summary' => [
                'required',
                'string',
                'max:5000',
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
            'after_photo_metadata' => ['nullable', 'array'],
        ];
    }
}
