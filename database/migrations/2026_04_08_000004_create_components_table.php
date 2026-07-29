<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabla para componentes mayores del vehiculo (requeridos por FLHSMV)
     */
    public function up(): void
    {
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_case_id');
            $table->foreign('user_case_id')->references('id')->on('user_cases')->onDelete('cascade');

            // Tipo de componente (segun Florida Statute 319.30)
            $table->enum('part_type', [
                // Componentes estandar
                'fender_front_left',
                'fender_front_right',
                'fender_rear_left',
                'fender_rear_right',
                'hood',
                'bumper_front',
                'bumper_rear',
                'cowl_assembly',
                'quarter_panel_left',
                'quarter_panel_right',
                'trunk_lid',
                'deck_lid',
                'door_front_left',
                'door_front_right',
                'door_rear_left',
                'door_rear_right',
                'floor_pan',
                'engine',
                'frame',
                'transmission',
                'catalytic_converter',
                'airbag_driver',
                'airbag_passenger',
                'airbag_side_left',
                'airbag_side_right',
                'airbag_curtain',
                // Componentes electricos/hibridos
                'electric_traction_motor',
                'electronic_transmission',
                'charge_port',
                'dc_power_converter',
                'onboard_charger',
                'power_electronics_controller',
                'thermal_system',
                'traction_battery',
                // Otros
                'other'
            ]);

            $table->string('other_description')->nullable(); // Si part_type es 'other'

            // Estado del componente
            $table->enum('condition', [
                'new',
                'used',
                'repaired',
                'rebuilt',
                'original'
            ]);

            // Origen del componente
            $table->string('source_name')->nullable(); // Nombre del vendedor/taller
            $table->string('source_phone')->nullable();
            $table->string('source_address')->nullable();
            $table->string('source_city')->nullable();
            $table->string('source_state', 2)->nullable();
            $table->string('source_zip', 10)->nullable();

            // Identificacion de la parte
            $table->string('part_number')->nullable();
            $table->string('serial_number')->nullable();

            // Informacion del recibo
            $table->unsignedBigInteger('receipt_file_id')->nullable();
            $table->foreign('receipt_file_id')->references('id')->on('uploaded_files')->onDelete('set null');

            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->string('receipt_number')->nullable();

            // Verificacion durante inspeccion
            $table->boolean('verified')->default(false);
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();

            $table->timestamps();

            // Indices
            $table->index(['user_case_id', 'part_type']);
            $table->index('verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('components');
    }
};
