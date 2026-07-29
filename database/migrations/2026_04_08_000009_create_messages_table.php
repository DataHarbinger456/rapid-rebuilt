<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabla para mensajes entre usuarios y staff
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_case_id');
            $table->foreign('user_case_id')->references('id')->on('user_cases')->onDelete('cascade');

            $table->unsignedBigInteger('sender_id');
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->foreign('recipient_id')->references('id')->on('users')->onDelete('set null');

            $table->text('message');

            // Archivos adjuntos
            $table->json('attachments')->nullable();

            // Flags
            $table->boolean('is_internal')->default(false); // Solo visible para staff
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            // Tipo de mensaje
            $table->enum('message_type', [
                'text',
                'document_request',
                'status_update',
                'system_notification',
                'reminder'
            ])->default('text');

            $table->timestamps();

            // Indices
            $table->index(['user_case_id', 'created_at']);
            $table->index(['recipient_id', 'is_read']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
