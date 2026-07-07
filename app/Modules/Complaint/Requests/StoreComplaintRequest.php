<?php

namespace App\Modules\Complaint\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        if (in_array($user->role, ['admin', 'agent'], true)) {
            return true;
        }

        if ($user->role === 'customer') {
            return Customer::where('id', $this->integer('customer_id'))
                ->where('user_id', $user->id)
                ->exists();
        }

        return false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'integer',
                'exists:customers,id',
            ],
            'service_area_id' => [
                'nullable',
                'integer',
                'exists:service_areas,id',
            ],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:5000'],
            'preferred_visit_time' => [
                'nullable',
                'date',
                'after:now',
            ],
            'priority' => [
                'sometimes',
                Rule::in(['low', 'medium', 'high', 'critical']),
            ],
        ];
    }
}
