<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UploadInspectionPhotoRequest extends FormRequest
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
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'photo_type' => 'required|string|in:front,rear,left,right,vin_plate,odometer,engine,damage,component,other',
            'description' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Please select a photo to upload.',
            'photo.image' => 'File must be an image.',
            'photo.mimes' => 'Photo must be a JPG or PNG.',
            'photo.max' => 'Photo size cannot exceed 5MB.',
            'photo_type.required' => 'Please specify the photo type.',
            'photo_type.in' => 'Invalid photo type selected.',
        ];
    }
}
