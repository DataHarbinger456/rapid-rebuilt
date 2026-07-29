<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDmvFormsTable extends Migration
{
    public function up()
    {
        Schema::create('dmv_forms', function (Blueprint $table) {
            $table->id();
            $table->enum('form_type', [
                'HSMV_default-form', 
                'HSMV_84490', 
                'HSMV_84491', 
                'HSMV_82050', 
                'HSMV_82053'
            ]);
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->boolean('is_completed')->default(false);
            $table->longText('form_data')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'form_type'], 'unique_user_form');

            // Common fields
            $table->longText('customer_number')->nullable();
            $table->longText('fleet_number')->nullable();
            $table->longText('unit_number')->nullable();
            $table->longText('owner_county')->nullable();
            $table->boolean('is_florida_resident')->nullable();
            $table->boolean('is_us_citizen')->nullable();
            $table->boolean('is_deaf_hard_of_hearing')->nullable();
            $table->longText('owner_name')->nullable();
            $table->longText('owner_phone')->nullable();
            $table->longText('owner_email')->nullable();
            $table->longText('owner_dl_id')->nullable();
            $table->date('owner_dob')->nullable();
            $table->longText('owner_address')->nullable();
            $table->longText('owner_city')->nullable();
            $table->longText('owner_state')->nullable();
            $table->longText('owner_zip')->nullable();
            $table->longText('mail_to_name')->nullable();
            $table->longText('mail_to_phone')->nullable();
            $table->longText('mail_to_email')->nullable();
            $table->longText('mail_to_address')->nullable();
            $table->longText('mail_to_city')->nullable();
            $table->longText('mail_to_state')->nullable();
            $table->longText('mail_to_zip')->nullable();

            // HSMV_default-form specific fields
            $table->boolean('is_original')->nullable();
            $table->boolean('is_transfer')->nullable();
            $table->longText('vin')->nullable();
            $table->longText('title_number')->nullable();
            $table->longText('license_plate_number')->nullable();
            $table->longText('previous_state_of_issue')->nullable();
            $table->longText('make')->nullable();
            $table->longText('model')->nullable();
            $table->integer('year')->nullable();
            $table->longText('body_type')->nullable();
            $table->longText('color')->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->longText('gvw')->nullable();
            $table->longText('bhp_cc')->nullable();
            $table->longText('odometer_reading')->nullable();
            $table->date('odometer_reading_date')->nullable();
            $table->boolean('odometer_actual_mileage')->nullable();
            $table->boolean('odometer_exceeds_limits')->nullable();
            $table->boolean('odometer_not_actual_mileage')->nullable();
            $table->longText('affidavit_1')->nullable();
            $table->longText('affidavit_2')->nullable();
            $table->longText('seller_signature')->nullable();
            $table->longText('seller_printed_name')->nullable();
            $table->date('seller_date')->nullable();
            $table->longText('co_seller_signature')->nullable();
            $table->longText('co_seller_printed_name')->nullable();
            $table->date('co_seller_date')->nullable();

            // HSMV_84490 specific fields
            $table->boolean('rebuilt')->nullable();
            $table->boolean('aspt')->nullable();
            $table->boolean('kit_car')->nullable();
            $table->longText('other')->nullable();
            $table->integer('year_84490')->nullable();
            $table->longText('make_84490')->nullable();
            $table->longText('id_84490')->nullable();
            $table->longText('color_84490')->nullable();
            $table->longText('body_84490')->nullable();
            $table->longText('length_84490')->nullable();
            $table->longText('title_number_84490')->nullable();
            $table->longText('title_state_84490')->nullable();
            $table->longText('other_title_number_84490')->nullable();
            $table->longText('other_title_state_84490')->nullable();
            $table->longText('major_component_parts')->nullable();
            $table->integer('number_of_receipts')->nullable();
            $table->boolean('custom_vehicle')->nullable();
            $table->boolean('street_rod')->nullable();
            $table->longText('tax_due_on')->nullable();
            $table->boolean('mobile_home_with_tongue')->nullable();
            $table->boolean('mobile_home_without_tongue')->nullable();
            $table->longText('comments_84490')->nullable();
            $table->longText('inspector_signature')->nullable();
            $table->longText('inspector_print_name')->nullable();
            $table->longText('inspector_region')->nullable();
            $table->date('inspector_date')->nullable();
            $table->longText('vin_84490')->nullable();
            $table->longText('odometer_84490')->nullable();
            $table->longText('audit_number')->nullable();
            $table->longText('region_number')->nullable();

            // HSMV_84491 specific fields
            $table->boolean('rebuilt_84491')->nullable();
            $table->boolean('aspt_84491')->nullable();
            $table->boolean('kit_car_84491')->nullable();
            $table->longText('other_84491')->nullable();
            $table->integer('year_84491')->nullable();
            $table->longText('make_84491')->nullable();
            $table->longText('id_84491')->nullable();
            $table->longText('color_84491')->nullable();
            $table->longText('body_84491')->nullable();
            $table->longText('length_84491')->nullable();
            $table->longText('repairs_description')->nullable();
            $table->date('date_84491')->nullable();
            $table->longText('applicant_name_84491')->nullable();
            $table->longText('applicant_signature_84491')->nullable();
            $table->longText('applicant_business_name_84491')->nullable();
            $table->longText('applicant_business_signature_84491')->nullable();

            // HSMV_82050 specific fields
            $table->boolean('notice_of_sale')->nullable();
            $table->boolean('bill_of_sale')->nullable();
            $table->integer('year_82050')->nullable();
            $table->longText('maker_82050')->nullable();
            $table->longText('body_type_82050')->nullable();
            $table->longText('model_82050')->nullable();
            $table->longText('color_82050')->nullable();
            $table->longText('title_number_82050')->nullable();
            $table->date('current_title_issue_date_82050')->nullable();
            $table->longText('vehicle_identification_number_82050')->nullable();
            $table->longText('purchaser_name_82050')->nullable();
            $table->longText('purchaser_address_82050')->nullable();
            $table->longText('purchaser_city_82050')->nullable();
            $table->longText('purchaser_state_82050')->nullable();
            $table->longText('purchaser_zipcode_82050')->nullable();
            $table->date('date_of_sale_82050')->nullable();
            $table->decimal('selling_price_82050', 10, 2)->nullable();
            $table->longText('odometer_reading_82050')->nullable();
            $table->date('odometer_reading_date_82050')->nullable();
            $table->boolean('odometer_actual_mileage_82050')->nullable();
            $table->boolean('odometer_exceeds_limits_82050')->nullable();
            $table->boolean('odometer_not_actual_mileage_82050')->nullable();
            $table->longText('affidavit_82050_1')->nullable();
            $table->longText('affidavit_82050_2')->nullable();
            $table->longText('seller_signature_82050')->nullable();
            $table->longText('seller_printed_name_82050')->nullable();
            $table->date('seller_date_82050')->nullable();
            $table->longText('co_seller_signature_82050')->nullable();
            $table->longText('co_seller_printed_name_82050')->nullable();
            $table->date('co_seller_date_82050')->nullable();
            $table->longText('purchaser_signature_82050')->nullable();
            $table->longText('purchaser_printed_name_82050')->nullable();
            $table->date('purchaser_date_82050')->nullable();
            $table->longText('co_purchaser_signature_82050')->nullable();
            $table->longText('co_purchaser_printed_name_82050')->nullable();
            $table->date('co_purchaser_date_82050')->nullable();

            // HSMV_82053 specific fields
            $table->date('power_of_attorney_date_82053')->nullable();
            $table->longText('attorney_in_fact_name_82053')->nullable();
            $table->boolean('motor_vehicle_82053')->nullable();
            $table->boolean('mobile_home_82053')->nullable();
            $table->boolean('vessel_82053')->nullable();
            $table->boolean('vessel_with_untitled_trailer_82053')->nullable();
            $table->boolean('vessel_with_titled_trailer_82053')->nullable();
            $table->integer('year_82053')->nullable();
            $table->longText('make_82053')->nullable();
            $table->longText('body_type_82053')->nullable();
            $table->longText('title_number_82053')->nullable();
            $table->longText('vin_82053')->nullable();
            $table->integer('year_82053_1')->nullable();
            $table->longText('make_82053_1')->nullable();
            $table->longText('body_type_82053_1')->nullable();
            $table->longText('title_number_82053_1')->nullable();
            $table->longText('vin_82053_1')->nullable();
            $table->longText('owner_name_82053')->nullable();
            $table->longText('owner_signature_82053')->nullable();
            $table->longText('owner_driver_license_number_82053')->nullable();
            $table->date('owner_date_of_birth_82053')->nullable();
            $table->longText('owner_address_82053')->nullable();
            $table->longText('owner_city_82053')->nullable();
            $table->longText('owner_state_82053')->nullable();
            $table->longText('owner_zipcode_82053')->nullable();
            $table->longText('co_owner_name_82053')->nullable();
            $table->longText('co_owner_signature_82053')->nullable();
            $table->longText('co_owner_driver_license_number_82053')->nullable();
            $table->date('co_owner_date_of_birth_82053')->nullable();
            $table->longText('co_owner_address_82053')->nullable();
            $table->longText('co_owner_city_82053')->nullable();
            $table->longText('co_owner_state_82053')->nullable();
            $table->longText('co_owner_zipcode_82053')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('dmv_forms');
    }
}