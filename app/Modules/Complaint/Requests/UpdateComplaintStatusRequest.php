<?php

namespace App\Modules\Complaint\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComplaintStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    'new',
                    'classified',
                    'assigned',
                    'technician_on_the_way',
                    'in_progress',
                    'resolved',
                    'closed',
                    'cancelled',
                    'escalated',
                ]),
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}