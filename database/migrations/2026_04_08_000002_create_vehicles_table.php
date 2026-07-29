<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabla para almacenar informacion detallada del vehiculo
     */
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_case_id');
            $table->foreign('user_case_id')->references('id')->on('user_cases')->onDelete('cascade');

            // Identificacion del vehiculo
            $table->string('vin', 17);
            $table->string('title_number')->nullable();
            $table->string('title_state', 2)->default('FL');
            $table->enum('title_brand', [
                'salvage',
                'certificate_of_destruction',
                'rebuilt',
                'clean',
                'flood',
                'junk',
                'other'
            ])->default('salvage');

            // Informacion basica del vehiculo
            $table->integer('year');
            $table->string('make');
            $table->string('model');
            $table->string('body_type')->nullable();
            $table->string('color')->nullable();
            $table->integer('weight')->nullable();
            $table->string('engine_type')->nullable();
            $table->string('fuel_type')->nullable();
            $table->string('license_plate')->nullable();
            $table->string('previous_state')->nullable();

            // Odometro
            $table->integer('odometer_reading')->nullable();
            $table->date('odometer_date')->nullable();
            $table->enum('odometer_status', [
                'actual',
                'exceeds_limits',
                'not_actual'
            ])->nullable();

            // Clasificacion para rebuilt
            $table->enum('vehicle_type', [
                'rebuilt',
                'aspt',
                'kit_car',
                'custom_vehicle',
                'street_rod',
                'replica',
                'mobile_home',
                'other'
            ])->nullable();

            // VIN decodificado (cache de API response)
            $table->json('vin_decoded_data')->nullable();

            $table->timestamps();

            // Indices
            $table->index('vin');
            $table->index('user_case_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
