<?php

namespace App\Services;

use App\Models\DMVForm;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FormDataStorageService
{
    public function storeFormData(array $formData, string $formType)
    {
        // Encrypt sensitive information
        // $encryptedData = $this->encryptSensitiveFields($formData);

        // Generate unique identifier
        $uniqueIdentifier = $this->generateUniqueFormIdentifier($formType);

        // Store form data
        $dmvForm = DMVForm::create([
            'form_type' => $formType,
           'form_data' => json_encode($formData),
            'user_id' => auth()->id(),
            'is_completed' => true,
            'customer_number' => $uniqueIdentifier
        ]);

        // Optional: Backup to secure storage
        $this->backupFormData($dmvForm);

        return $dmvForm;
    }

    // Encrypt sensitive fields
    private function encryptSensitiveFields(array $formData)
    {
        $sensitiveFields = [
            'owner_ssn', 
            'owner_dl_number', 
            'credit_card_info'
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($formData[$field])) {
                $formData[$field] = encrypt($formData[$field]);
            }
        }

        return $formData;
    }

    // Generate unique form identifier
    private function generateUniqueFormIdentifier(string $formType)
    {
        return strtoupper($formType) . '-' . 
               now()->format('Ymd') . '-' . 
               Str::random(6);
    }

    // Backup form data to secure storage
    private function backupFormData(DMVForm $form)
    {
        $backupPath = 'dmv_forms_backup/' . 
                      $form->form_type . '/' . 
                      $form->customer_number . '.json';

        Storage::disk('secure')->put(
            $backupPath, 
            json_encode($form->form_data)
        );
    }

    // Retrieve and decrypt form data
    public function retrieveFormData(DMVForm $form)
    {
        $formData = $form->form_data;

        $decryptedData = array_map(function($value) {
            // Attempt to decrypt, return original if not encrypted
            try {
                return is_string($value) ? decrypt($value) : $value;
            } catch (\Exception $e) {
                return $value;
            }
        }, $formData);

        return $decryptedData;
    }
}