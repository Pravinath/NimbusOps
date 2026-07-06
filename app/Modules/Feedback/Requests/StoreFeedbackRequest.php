<?php

namespace App\Modules\Feedback\Requests;

use App\Models\Complaint;
use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->user()?->role !== 'customer') {
            return false;
        }

        return Complaint::query()
            ->where('id', $this->integer('complaint_id'))
            ->whereHas('customer', function ($query) {
                $query->where('user_id', $this->user()->id);
            })
            ->exists();
    }

    public function rules(): array
    {
        return [
            'complaint_id' => [
                'required',
                'integer',
                'exists:complaints,id',
                'unique:feedback,complaint_id',
            ],
            'work_order_id' => [
                'required',
                'integer',
                'exists:work_orders,id',
                'unique:feedback,work_order_id',
            ],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:3000'],
        ];
    }
}