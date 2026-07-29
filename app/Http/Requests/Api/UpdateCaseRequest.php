<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCaseRequest extends FormRequest
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
            'case_type' => 'sometimes|in:rebuilt,salvage,bonded',
            'rush_processing' => 'sometimes|boolean',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
