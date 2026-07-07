<?php

namespace App\Modules\Technician\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'availability_status' => [
                'required',
                Rule::in(['available', 'busy', 'offline', 'on_leave']),
            ],
        ];
    }
}
