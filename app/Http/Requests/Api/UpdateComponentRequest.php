<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateComponentRequest extends FormRequest
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
            'part_type' => 'sometimes|string',
            'condition' => 'sometimes|in:new,used,repaired,original',
            'source' => 'nullable|string|max:255',
            'source_phone' => 'nullable|string|max:20',
            'source_address' => 'nullable|string|max:255',
            'part_number' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
        ];
    }
}
