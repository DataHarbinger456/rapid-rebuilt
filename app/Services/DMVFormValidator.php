<?php



namespace App\Services;

use Illuminate\Support\Facades\Validator;

class DMVFormValidator
{
    public function validateForm($formData, $formType)
    {
        // Define validation rules for each form type
        $validationRules = [
            'HSMV_default-form' => [
                'customer_number' => 'required|string|max:255',
                'owner_name' => 'required|string|max:255',
                'owner_email' => 'required|email|max:255',
                'owner_phone' => 'required|string|max:15',
                // Add other validation rules for this form type
            ],
            'HSMV_84490' => [
                'vin' => 'required|string|max:17',
                'title_number' => 'required|string|max:50',
                'vehicle_year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
                'make' => 'required|string|max:255',
                'model' => 'required|string|max:255',
                // Add other validation rules for this form type
            ],
            'HSMV_82040' => [
                'customer_number' => 'required|string|max:255',
                'fleet_number' => 'nullable|string|max:255',
                'unit_number' => 'nullable|string|max:255',
                'owner_county' => 'required|string|max:255',
                'is_florida_resident' => 'required|boolean',
                'is_us_citizen' => 'required|boolean',
                'is_deaf_hard_of_hearing' => 'nullable|boolean',
                'owner_name' => 'required|string|max:255',
                'owner_phone' => 'required|string|max:15',
                'owner_email' => 'required|email|max:255',
                'owner_dl_id' => 'nullable|string|max:50',
                'owner_dob' => 'nullable|date',
                'owner_address' => 'required|string|max:255',
                'owner_city' => 'required|string|max:255',
                'owner_state' => 'required|string|max:2', // Assuming state is a 2-letter code
                'owner_zip' => 'required|string|max:10',
                'mail_to_name' => 'nullable|string|max:255',
                'mail_to_phone' => 'nullable|string|max:15',
                'mail_to_email' => 'nullable|email|max:255',
                'mail_to_address' => 'nullable|string|max:255',
                'mail_to_city' => 'nullable|string|max:255',
                'mail_to_state' => 'nullable|string|max:2',
                'mail_to_zip' => 'nullable|string|max:10',
                'vin' => 'required|string|max:17',
                'title_number' => 'required|string|max:50',
                'license_plate_number' => 'nullable|string|max:20',
                'previous_state_of_issue' => 'nullable|string|max:50',
                'make' => 'required|string|max:255',
                'model' => 'required|string|max:255',
                'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
                'body_type' => 'nullable|string|max:50',
                'color' => 'nullable|string|max:50',
                'weight' => 'nullable|numeric',
                'gvw' => 'nullable|string|max:50',
                'bhp_cc' => 'nullable|numeric',
                'odometer_reading' => 'nullable|numeric',
                'odometer_reading_date' => 'nullable|date',
                'odometer_actual_mileage' => 'nullable|boolean',
                'odometer_exceeds_limits' => 'nullable|boolean',
                'odometer_not_actual_mileage' => 'nullable|boolean',
                // Add other specific validation rules for HSMV_82040
            ],
            // Add other form types and their validation rules
        ];

        // Check if the form type exists in the validation rules
        if (!array_key_exists($formType, $validationRules)) {
            throw new \Exception('Invalid form type: ' . $formType);
        }

        // Validate form data
        $validator = Validator::make($formData, $validationRules[$formType]);

        if ($validator->fails()) {
            throw new \Exception('Invalid form data: ' . $validator->errors()->first());
        }

        return $validator->validated();
    }
}