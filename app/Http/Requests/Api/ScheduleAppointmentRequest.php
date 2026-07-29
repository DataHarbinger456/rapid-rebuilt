<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class ScheduleAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('case');
        $user = $this->user();

        return $user->isStaff() || $case->user_id === $user->id;
    }

    public function rules(): array
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        return [
            'date' => "required|date|after_or_equal:{$tomorrow}",
            'time' => 'required|date_format:H:i',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Please select an appointment date.',
            'date.after_or_equal' => 'Appointment must be scheduled for tomorrow or later.',
            'time.required' => 'Please select an appointment time.',
            'time.date_format' => 'Invalid time format. Use HH:MM format.',
        ];
    }
}
