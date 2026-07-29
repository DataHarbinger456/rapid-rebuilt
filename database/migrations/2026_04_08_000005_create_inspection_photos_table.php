<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabla para fotos tomadas durante la inspeccion
     */
    public function up(): void
    {
        Schema::create('inspection_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inspection_id');
            $table->foreign('inspection_id')->references('id')->on('inspections')->onDelete('cascade');

            $table->unsignedBigInteger('uploaded_file_id');
            $table->foreign('uploaded_file_id')->references('id')->on('uploaded_files')->onDelete('cascade');

            // Tipo de foto
            $table->enum('photo_type', [
                // Fotos de VIN
                'vin_windshield',
                'vin_doorjamb',
                'vin_engine',
                'vin_sticker',
                // Fotos de odometro
                'odometer',
                // Fotos exteriores
                'exterior_front',
                'exterior_rear',
                'exterior_left',
                'exterior_right',
                'exterior_front_left',
                'exterior_front_right',
                'exterior_rear_left',
                'exterior_rear_right',
                // Fotos interiores
                'interior_dashboard',
                'interior_front_seats',
                'interior_rear_seats',
                'interior_trunk',
                // Fotos de componentes
                'component_engine',
                'component_frame',
                'component_transmission',
                'component_other',
                // Fotos de dano/reparacion
                'damage_before',
                'repair_after',
                // Otras
                'other'
            ]);

            $table->string('description')->nullable();

            // Metadatos de la foto
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamp('taken_at')->nullable();
            $table->string('device_info')->nullable();

            // Referencia a componente si es foto de componente
            $table->unsignedBigInteger('component_id')->nullable();
            $table->foreign('component_id')->references('id')->on('components')->onDelete('set null');

            $table->timestamps();

            // Indices
            $table->index(['inspection_id', 'photo_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_photos');
    }
};
