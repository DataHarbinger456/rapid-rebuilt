<?php

namespace App\Http\Requests\Admin;

use App\Models\UserCase;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isStaff();
    }

    public function rules(): array
    {
        $validStatuses = implode(',', array_keys(UserCase::STATUSES));

        return [
            'status' => "required|string|in:{$validStatuses}",
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Please select a status.',
            'status.in' => 'Invalid status selected.',
        ];
    }
}
