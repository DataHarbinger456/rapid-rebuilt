<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'case_type' => 'required|in:rebuilt,salvage,bonded',
            'rush_processing' => 'boolean',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'case_type.required' => 'Please select a case type.',
            'case_type.in' => 'Invalid case type selected.',
        ];
    }
}
