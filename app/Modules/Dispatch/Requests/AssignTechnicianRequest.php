<?php

namespace App\Modules\Dispatch\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTechnicianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(
            $this->user()?->role,
            ['dispatcher', 'admin'],
            true
        );
    }

    public function rules(): array
    {
        return [
            'technician_id' => [
                'required',
                'integer',
                'exists:technicians,id',
            ],
            'scheduled_visit_time' => [
                'nullable',
                'date',
                'after:now',
            ],
            'override' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
