<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AssignInspectorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isStaff();
    }

    public function rules(): array
    {
        return [
            'inspector_id' => 'required|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'inspector_id.required' => 'Please select an inspector.',
            'inspector_id.exists' => 'Selected inspector does not exist.',
        ];
    }
}
