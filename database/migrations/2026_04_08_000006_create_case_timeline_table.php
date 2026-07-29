<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabla para el historial de eventos de un caso
     */
    public function up(): void
    {
        Schema::create('case_timeline', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_case_id');
            $table->foreign('user_case_id')->references('id')->on('user_cases')->onDelete('cascade');

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Tipo de evento
            $table->enum('event_type', [
                'case_created',
                'case_submitted',
                'case_assigned',
                'status_changed',
                'document_uploaded',
                'document_approved',
                'document_rejected',
                'document_requested',
                'payment_initiated',
                'payment_received',
                'payment_failed',
                'appointment_scheduled',
                'appointment_confirmed',
                'appointment_rescheduled',
                'appointment_cancelled',
                'appointment_no_show',
                'inspection_started',
                'inspection_paused',
                'inspection_completed',
                'inspection_passed',
                'inspection_failed',
                'documents_generated',
                'documents_printed',
                'sent_to_dmv',
                'dmv_received',
                'title_issued',
                'case_completed',
                'case_cancelled',
                'note_added',
                'message_sent',
                'reminder_sent',
                'other'
            ]);

            $table->string('title');
            $table->text('description')->nullable();

            // Metadatos adicionales (ej: estado anterior, nuevo estado, etc.)
            $table->json('metadata')->nullable();

            // Para eventos automaticos vs manuales
            $table->boolean('is_system_event')->default(false);

            $table->timestamps();

            // Indices
            $table->index(['user_case_id', 'created_at']);
            $table->index('event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_timeline');
    }
};
