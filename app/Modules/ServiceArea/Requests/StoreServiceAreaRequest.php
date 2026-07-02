<?php

namespace App\Modules\ServiceArea\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'zone' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'in:active,inactive'],
        ];
    }
}