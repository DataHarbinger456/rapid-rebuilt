<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mejoras a la tabla de citas/schedulings
     */
    public function up(): void
    {
        Schema::table('user_form_schedulings', function (Blueprint $table) {
            // Inspector asignado
            $table->unsignedBigInteger('inspector_id')->nullable()->after('user_case_id');
            $table->foreign('inspector_id')->references('id')->on('users')->onDelete('set null');

            // Duracion en minutos
            $table->integer('duration_minutes')->default(30)->after('end');

            // Ubicacion detallada
            $table->string('location_name')->nullable()->after('subject');
            $table->string('location_address')->nullable()->after('location_name');
            $table->string('location_city')->nullable()->after('location_address');
            $table->string('location_state', 2)->default('FL')->after('location_city');
            $table->string('location_zip', 10)->nullable()->after('location_state');
            $table->decimal('location_lat', 10, 8)->nullable()->after('location_zip');
            $table->decimal('location_lng', 11, 8)->nullable()->after('location_lat');

            // Notas
            $table->text('customer_notes')->nullable()->after('status');
            $table->text('inspector_notes')->nullable()->after('customer_notes');

            // Recordatorios enviados
            $table->boolean('reminder_24h_sent')->default(false)->after('inspector_notes');
            $table->boolean('reminder_1h_sent')->default(false)->after('reminder_24h_sent');
            $table->boolean('confirmation_sent')->default(false)->after('reminder_1h_sent');

            // Motivo de cancelacion/reprogramacion
            $table->string('status_reason')->nullable()->after('confirmation_sent');

            // Cita anterior (si fue reprogramada)
            $table->unsignedBigInteger('rescheduled_from_id')->nullable()->after('status_reason');
            $table->foreign('rescheduled_from_id')->references('id')->on('user_form_schedulings')->onDelete('set null');

            // Indices
            $table->index('inspector_id');
            $table->index(['start', 'status']);
        });

        // Actualizar el enum de status
        DB::statement("ALTER TABLE `user_form_schedulings` MODIFY COLUMN `status` ENUM('pending', 'confirmed', 'in_progress', 'completed', 'no_show', 'cancelled', 'rescheduled') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_form_schedulings', function (Blueprint $table) {
            $table->dropForeign(['inspector_id']);
            $table->dropForeign(['rescheduled_from_id']);
            $table->dropColumn([
                'inspector_id',
                'duration_minutes',
                'location_name',
                'location_address',
                'location_city',
                'location_state',
                'location_zip',
                'location_lat',
                'location_lng',
                'customer_notes',
                'inspector_notes',
                'reminder_24h_sent',
                'reminder_1h_sent',
                'confirmation_sent',
                'status_reason',
                'rescheduled_from_id'
            ]);
        });
    }
};
