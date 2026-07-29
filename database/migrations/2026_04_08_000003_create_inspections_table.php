<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabla para almacenar los resultados de las inspecciones
     */
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_case_id');
            $table->foreign('user_case_id')->references('id')->on('user_cases')->onDelete('cascade');

            $table->unsignedBigInteger('scheduling_id')->nullable();
            $table->foreign('scheduling_id')->references('id')->on('user_form_schedulings')->onDelete('set null');

            $table->unsignedBigInteger('inspector_id');
            $table->foreign('inspector_id')->references('id')->on('users');

            // Resultado de la inspeccion
            $table->enum('result', [
                'pending',
                'in_progress',
                'passed',
                'failed',
                'incomplete'
            ])->default('pending');

            // Verificacion de VIN
            $table->boolean('vin_verified')->default(false);
            $table->string('vin_location_1')->nullable(); // VIN bajo parabrisas
            $table->string('vin_location_2')->nullable(); // VIN marco puerta
            $table->boolean('vin_plates_match')->default(false);
            $table->boolean('vin_no_alterations')->default(false);

            // Verificacion de odometro
            $table->integer('odometer_reading_verified')->nullable();
            $table->boolean('odometer_functional')->default(false);

            // Componentes verificados (JSON con el estado de cada uno)
            $table->json('components_verified')->nullable();
            $table->boolean('all_receipts_match')->default(false);

            // Sistemas de seguridad
            $table->boolean('brakes_functional')->default(false);
            $table->boolean('lights_functional')->default(false);
            $table->boolean('steering_functional')->default(false);
            $table->boolean('horn_functional')->default(false);
            $table->boolean('wipers_functional')->default(false);
            $table->boolean('airbags_present')->nullable();
            $table->boolean('seatbelts_functional')->default(false);

            // Notas y comentarios
            $table->text('inspector_notes')->nullable();
            $table->text('failure_reasons')->nullable();

            // Firma digital del inspector
            $table->longText('inspector_signature')->nullable(); // Base64
            $table->timestamp('signed_at')->nullable();

            // Datos de auditoria (PRVIP)
            $table->string('audit_number')->nullable();
            $table->string('region_number')->nullable();
            $table->string('decal_number')->nullable(); // Numero de calcomania rebuilt

            // Timestamps
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indices
            $table->index(['user_case_id', 'result']);
            $table->index('inspector_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
