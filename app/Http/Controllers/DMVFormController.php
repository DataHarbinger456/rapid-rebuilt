<?php

namespace App\Http\Controllers;

use App\Models\DMVForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\FormDataStorageService;
use App\Services\DMVFormValidator;

class DMVFormController extends Controller
{
    public function create($formType)
    {
        // Render form view based on form type
        return view('dmv_forms.'.$formType);
    }

    public function store(Request $request, $formType, FormDataStorageService $storageService, DMVFormValidator $validator)
{
    // Validate form submission
    $validatedData = $validator->validateForm($request->all(), $formType);

    // Store form data
    $dmvForm = $storageService->storeFormData($validatedData, $formType);

    // Check user type and previous submissions
    $user = Auth::user();

    // For regular users, check if form was already submitted
    if ($user->user_type == 'user') {
        $existingForm = DMVForm::where('user_id', $user->id)
            ->where('form_type', $formType)
            ->first();

        if ($existingForm) {
            return redirect()->back()->with('error', 'You have already submitted this form.');
        }
    }

    // Create form submission
    $dmvForm = DMVForm::create([
        'user_id' => $user->id,
        'form_type' => $formType,
        'is_completed' => true,
        'form_data' => json_encode($validatedData)
    ]);

    // Store specific form fields
    foreach ($validatedData as $key => $value) {
        if (in_array($key, [
            'customer_number', 'fleet_number', 'unit_number', 'owner_county',
            'is_florida_resident', 'is_us_citizen', 'is_deaf_hard_of_hearing',
            'owner_name', 'owner_phone', 'owner_email', 'owner_dl_id', 'owner_dob',
            'owner_address', 'owner_city', 'owner_state', 'owner_zip',
            'mail_to_name', 'mail_to_phone', 'mail_to_email', 'mail_to_address',
            'mail_to_city', 'mail_to_state', 'mail_to_zip',
            'vin', 'title_number', 'license_plate_number', 'previous_state_of_issue',
            'make', 'model', 'year', 'body_type', 'color', 'weight', 'gvw', 'bhp_cc',
            'odometer_reading', 'odometer_reading_date', 'odometer_actual_mileage',
            'odometer_exceeds_limits', 'odometer_not_actual_mileage',
            'affidavit_1', 'affidavit_2', 'seller_signature', 'seller_printed_name',
            'seller_date', 'co_seller_signature', 'co_seller_printed_name', 'co_seller_date',
            'purchaser_signature', 'purchaser_printed_name', 'purchaser_date',
            'co_purchaser_signature', 'co_purchaser_printed_name', 'co_purchaser_date',
            'rebuilt', 'aspt', 'kit_car', 'other', 'year_84490', 'make_84490', 'id_84490',
            'color_84490', 'body_84490', 'length_84490', 'title_number_84490', 'title_state_84490',
            'other_title_number_84490', 'other_title_state_84490', 'major_component_parts',
            'number_of_receipts', 'custom_vehicle', 'street_rod', 'tax_due_on', 'mobile_home_with_tongue',
            'mobile_home_without_tongue', 'comments_84490', 'inspector_signature', 'inspector_print_name',
            'inspector_region', 'inspector_date', 'vin_84490', 'odometer_84490', 'audit_number', 'region_number',
            'rebuilt_84491', 'aspt_84491', 'kit_car_84491', 'other_84491', 'year_84491', 'make_84491', 'id_84491',
            'color_84491', 'body_84491', 'length_84491', 'repairs_description', 'date_84491', 'applicant_name_84491',
            'applicant_signature_84491', 'applicant_business_name_84491', 'applicant_business_signature_84491',
            'notice_of_sale', 'bill_of_sale', 'year_82050', 'maker_82050', 'body_type_82050', 'model_82050', 'color_82050',
            'title_number_82050', 'current_title_issue_date_82050', 'vehicle_identification_number_82050', 'purchaser_name_82050',
            'purchaser_address_82050', 'purchaser_city_82050', 'purchaser_state_82050', 'purchaser_zipcode_82050', 'date_of_sale_82050',
            'selling_price_82050', 'odometer_reading_82050', 'odometer_reading_date_82050', 'odometer_actual_mileage_82050',
            'odometer_exceeds_limits_82050', 'odometer_not_actual_mileage_82050', 'affidavit_82050_1', 'affidavit_82050_2',
            'seller_signature_82050', 'seller_printed_name_82050', 'seller_date_82050', 'co_seller_signature_82050', 'co_seller_printed_name_82050',
            'co_seller_date_82050', 'purchaser_signature_82050', 'purchaser_printed_name_82050', 'purchaser_date_82050', 'co_purchaser_signature_82050',
            'co_purchaser_printed_name_82050', 'co_purchaser_date_82050', 'power_of_attorney_date_82053', 'attorney_in_fact_name_82053', 'motor_vehicle_82053',
            'mobile_home_82053', 'vessel_82053', 'vessel_with_untitled_trailer_82053', 'vessel_with_titled_trailer_82053', 'year_82053', 'make_82053', 'body_type_82053',
            'title_number_82053', 'vin_82053', 'year_82053_1', 'make_82053_1', 'body_type_82053_1', 'title_number_82053_1', 'vin_82053_1', 'owner_name_82053', 'owner_signature_82053',
            'owner_driver_license_number_82053', 'owner_date_of_birth_82053', 'owner_address_82053', 'owner_city_82053', 'owner_state_82053', 'owner_zipcode_82053', 'co_owner_name_82053',
            'co_owner_signature_82053', 'co_owner_driver_license_number_82053', 'co_owner_date_of_birth_82053', 'co_owner_address_82053', 'co_owner_city_82053', 'co_owner_state_82053', 'co_owner_zipcode_82053'
        ])) {
            $dmvForm->{$key} = $value;
        }
    }

    $dmvForm->save();

    return redirect()->route('forms.success')->with('message', 'Form submitted successfully');
}
    public function list()
    {
        $user = Auth::user();
        $forms = DMVForm::where('user_id', $user->id)->get();
        return view('dmv_forms.list', compact('forms'));
    }
}