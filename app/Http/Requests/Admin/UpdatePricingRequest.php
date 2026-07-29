<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'base_price' => 'required|numeric|min:0|max:9999.99',
            'rush_fee' => 'required|numeric|min:0|max:9999.99',
            'component_price' => 'required|numeric|min:0|max:999.99',
            'mobile_inspection_fee' => 'required|numeric|min:0|max:999.99',
        ];
    }

    public function messages(): array
    {
        return [
            'base_price.required' => 'Please enter the base price.',
            'base_price.numeric' => 'Base price must be a number.',
            'rush_fee.required' => 'Please enter the rush processing fee.',
            'component_price.required' => 'Please enter the component price.',
            'mobile_inspection_fee.required' => 'Please enter the mobile inspection fee.',
        ];
    }
}
