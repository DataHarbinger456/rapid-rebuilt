<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DMVFormValidator
{
    // Complex validation with dynamic rules
    public function validateForm(array $data, string $formType)
    {
        $rules = $this->generateValidationRules($formType);
        
        return Validator::make($data, $rules, $this->customValidationMessages());
    }

    private function generateValidationRules(string $formType)
    {
        $baseRules = [
            'owner_name' => [
                'required', 
                'string', 
                'min:2', 
                'max:255', 
                'regex:/^[a-zA-Z\s\-\']+$/'
            ],
            'owner_email' => ['required', 'email', 'max:255'],
            'owner_phone' => [
                'required', 
                'regex:/^\+?1?\d{10,14}$/', 
                'phone_number'
            ]
        ];

        $specificRules = match($formType) {
            'HSMV_default-form' => [
                'vin' => [
                    'required', 
                    'size:17', 
                    'regex:/^[A-HJ-NPR-Z0-9]{17}$/', 
                    'vin_checksum'
                ],
                'vehicle_year' => [
                    'required', 
                    'integer', 
                    'min:1985', 
                    'max:' . (date('Y') + 1)
                ]
            ],
            'HSMV_84490' => [
                'vehicle_type' => [
                    'required', 
                    Rule::in(['car', 'truck', 'motorcycle', 'other'])
                ],
                'rebuilt_status' => 'boolean'
            ],
            default => []
        };

        return array_merge($baseRules, $specificRules);
    }

    private function customValidationMessages()
    {
        return [
            'vin.regex' => 'Invalid Vehicle Identification Number format',
            'owner_name.regex' => 'Name contains invalid characters',
            'owner_phone.regex' => 'Invalid phone number format',
            'vehicle_year.min' => 'Vehicle year must be after 1985',
            'vehicle_year.max' => 'Vehicle year cannot be in the future'
        ];
    }

    // Advanced custom validation
    public function registerCustomValidationRules()
    {
        // Custom VIN validation
        Validator::extend('vin_checksum', function($attribute, $value, $parameters, $validator) {
            // Implement VIN checksum algorithm
            return $this->validateVinChecksum($value);
        });

        // Phone number validation
        Validator::extend('phone_number', function($attribute, $value, $parameters, $validator) {
            return preg_match('/^\+?1?\d{10,14}$/', $value);
        });
    }

    private function validateVinChecksum($vin)
    {
        // Implement complex VIN checksum validation
        // This is a placeholder - actual implementation would be more complex
        return strlen($vin) === 17;
    }
}