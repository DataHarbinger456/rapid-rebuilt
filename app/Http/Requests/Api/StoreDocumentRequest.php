<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('case');
        $user = $this->user();

        return $user->isStaff() || $case->user_id === $user->id;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'document_type' => 'required|string|in:title,salvage_certificate,bill_of_sale,insurance_settlement,id_front,id_back,component_receipts,photos,power_of_attorney,other',
            'description' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.mimes' => 'File must be a PDF, JPG, or PNG.',
            'file.max' => 'File size cannot exceed 10MB.',
            'document_type.required' => 'Please select a document type.',
            'document_type.in' => 'Invalid document type selected.',
        ];
    }
}
