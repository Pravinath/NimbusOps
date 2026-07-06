<?php

namespace App\Modules\WorkOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkOrderActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}