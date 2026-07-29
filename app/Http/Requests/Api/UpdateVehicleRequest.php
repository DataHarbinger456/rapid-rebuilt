<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('case');
        $user = $this->user();

        // Only owner can update draft cases, staff can update any
        return $user->isStaff() || ($case->user_id === $user->id && $case->status === 'draft');
    }

    public function rules(): array
    {
        $currentYear = date('Y') + 1;

        return [
            'vin' => 'sometimes|string|size:17',
            'year' => "sometimes|integer|min:1900|max:{$currentYear}",
            'make' => 'sometimes|string|max:100',
            'model' => 'sometimes|string|max:100',
            'body_type' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'title_number' => 'nullable|string|max:50',
            'title_state' => 'nullable|string|size:2',
            'title_brand' => 'nullable|string',
            'odometer_reading' => 'nullable|integer|min:0',
            'odometer_status' => 'nullable|in:actual,exceeds_limits,not_actual',
        ];
    }

    public function messages(): array
    {
        return [
            'vin.size' => 'VIN must be exactly 17 characters.',
            'year.min' => 'Vehicle year must be 1900 or later.',
            'year.max' => 'Vehicle year cannot be in the future.',
            'title_state.size' => 'Title state must be a 2-letter abbreviation.',
        ];
    }
}
