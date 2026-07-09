<?php

namespace App\Modules\TechnicianApplication\Requests;

use App\Models\TechnicianApplicationDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadTechnicianApplicationDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type' => [
                'required',
                'string',
                Rule::in(TechnicianApplicationDocument::TYPES),
            ],
            'document' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }
}