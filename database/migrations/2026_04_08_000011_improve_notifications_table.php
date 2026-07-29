<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mejoras a la tabla de notificaciones
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Usuario destinatario
            $table->unsignedBigInteger('user_id')->nullable()->after('notification');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Tipo de notificacion
            $table->string('type')->default('info')->after('user_case_id');

            // Titulo
            $table->string('title')->nullable()->after('type');

            // URL de accion
            $table->string('action_url')->nullable()->after('status');

            // Fecha de lectura
            $table->timestamp('read_at')->nullable()->after('action_url');

            // Datos adicionales
            $table->json('data')->nullable()->after('read_at');

            // Indices
            $table->index('user_id');
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn([
                'user_id',
                'type',
                'title',
                'action_url',
                'read_at',
                'data'
            ]);
        });
    }
};
