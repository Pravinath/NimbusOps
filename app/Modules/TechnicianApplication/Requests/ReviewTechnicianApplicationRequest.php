<?php

namespace App\Modules\TechnicianApplication\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewTechnicianApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    'under_review',
                    'information_required',
                    'interview_scheduled',
                    'rejected',
                ]),
            ],
            'review_notes' => ['nullable', 'string', 'max:3000'],
            'rejection_reason' => [
                Rule::requiredIf($this->input('status') === 'rejected'),
                'nullable',
                'string',
                'max:3000',
            ],
        ];
    }
}
