<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreComponentRequest extends FormRequest
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
            'part_type' => 'required|string',
            'condition' => 'required|in:new,used,repaired,original',
            'source' => 'nullable|string|max:255',
            'source_phone' => 'nullable|string|max:20',
            'source_address' => 'nullable|string|max:255',
            'part_number' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'part_type.required' => 'Please specify the component type.',
            'condition.required' => 'Please specify the component condition.',
            'condition.in' => 'Invalid condition. Must be new, used, repaired, or original.',
        ];
    }
}
