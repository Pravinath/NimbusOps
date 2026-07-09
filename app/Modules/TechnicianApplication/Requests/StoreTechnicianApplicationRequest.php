<?php

namespace App\Modules\TechnicianApplication\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTechnicianApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $skills = [
            'network',
            'electrical',
            'plumbing',
            'ac',
            'appliance',
            'facility',
            'general',
        ];

        return [
            'full_name' => ['required', 'string', 'max:120'],
            'date_of_birth' => [
                'required',
                'date',
                'before_or_equal:'.now()->subYears(18)->toDateString(),
            ],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'years_experience' => ['required', 'integer', 'min:0', 'max:60'],
            'highest_qualification' => ['required', 'string', 'max:255'],
            'skills' => ['required', 'array', 'min:1'],
            'skills.*' => ['required', 'distinct', Rule::in($skills)],
            'preferred_service_area_id' => [
                'nullable',
                'integer',
                'exists:service_areas,id',
            ],
            'motivation' => ['required', 'string', 'min:30', 'max:3000'],
        ];
    }
}
