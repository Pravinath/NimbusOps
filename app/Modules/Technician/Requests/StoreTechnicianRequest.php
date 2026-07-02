<?php

namespace App\Modules\Technician\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTechnicianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                'unique:technicians,user_id',
            ],
            'service_area_id' => [
                'nullable',
                'integer',
                'exists:service_areas,id',
            ],
            'skill_category' => [
                'required',
                Rule::in([
                    'network',
                    'electrical',
                    'plumbing',
                    'ac',
                    'appliance',
                    'facility',
                    'general',
                ]),
            ],
            'availability_status' => [
                'sometimes',
                Rule::in(['available', 'busy', 'offline', 'on_leave']),
            ],
        ];
    }
}