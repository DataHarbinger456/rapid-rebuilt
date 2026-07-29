<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $inspection = $this->route('inspection');
        $user = $this->user();

        return $user->isStaff() || $inspection->inspector_id === $user->id;
    }

    public function rules(): array
    {
        return [
            'checklist_data' => 'sometimes|array',
            'checklist_data.*' => 'boolean',
            'notes' => 'nullable|string|max:2000',
            'deficiencies' => 'nullable|string|max:2000',
        ];
    }
}
