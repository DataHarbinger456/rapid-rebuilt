<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\InvalidFormDataException;
use App\Exceptions\InvalidFormSubmissionException;

class DMVForm extends Model
{
    use HasFactory;

    protected $table = 'dmv_forms';

    protected $fillable = [
        'user_id', 'form_type', 'is_completed', 'form_data',
        'customer_number', 'owner_name', 'owner_email'
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'form_data' => 'array',
        'created_at' => 'datetime'
    ];

    // Advanced form type validation
    public static function validateFormData(array $data, string $formType)
    {
        $validationRules = self::getValidationRulesForFormType($formType);
        
        $validator = Validator::make($data, $validationRules);
        
        if ($validator->fails()) {
            throw new InvalidFormDataException(
                'Invalid form data: ' . $validator->errors()->first()
            );
        }

        return $validator->validated();
    }

    // Dynamic validation rules generator
    private static function getValidationRulesForFormType(string $formType)
    {
        $baseRules = [
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email',
            'owner_phone' => 'required|phone_number'
        ];

        $specificRules = match($formType) {
            'HSMV_default-form' => [
                'vin' => 'required|vin_number',
                'title_number' => 'required|string|max:50',
                'vehicle_year' => 'required|integer|min:1900|max:' . (date('Y') + 1)
            ],
            'HSMV_84490' => [
                'vehicle_type' => 'required|in:car,truck,motorcycle',
                'rebuilt_status' => 'boolean'
            ],
            default => []
        };

        return array_merge($baseRules, $specificRules);
    }

    // Advanced form data processing
    public function processFormData()
    {
        $formData = $this->form_data;
        
        // Custom processing logic
        $processedData = collect($formData)
            ->mapWithKeys(function ($value, $key) {
                // Custom transformations
                return [
                    $key => $this->sanitizeFormValue($key, $value)
                ];
            })
            ->toArray();

        $this->update(['form_data' => $processedData]);
        return $processedData;
    }

    // Value sanitization method
    private function sanitizeFormValue($key, $value)
    {
        // Custom sanitization logic
        if (is_string($value)) {
            $value = trim($value);
        }

        // Add more specific sanitization based on key
        return match($key) {
            'owner_phone' => preg_replace('/[^0-9]/', '', $value),
            'owner_email' => strtolower($value),
            default => $value
        };
    }

    // Advanced querying methods
    public function scopeRecentForms($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeIncompleteUserForms($query, $userId)
    {
        return $query->where('user_id', $userId)
                     ->where('is_completed', false);
    }
}