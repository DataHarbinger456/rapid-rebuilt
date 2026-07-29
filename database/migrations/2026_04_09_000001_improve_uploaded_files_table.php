<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mejoras a la tabla uploaded_files para soportar DocumentService
     */
    public function up(): void
    {
        Schema::table('uploaded_files', function (Blueprint $table) {
            // Relaciones
            $table->unsignedBigInteger('user_case_id')->nullable()->after('id');
            $table->foreign('user_case_id')->references('id')->on('user_cases')->onDelete('cascade');

            $table->unsignedBigInteger('uploaded_by')->nullable()->after('user_case_id');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');

            // Tipo de documento
            $table->string('document_type', 50)->nullable()->after('uploaded_by');

            // Renombrar y agregar campos de archivo
            $table->string('file_path')->nullable()->after('document_type');
            $table->integer('file_size')->unsigned()->nullable()->after('file_path');

            // Estado de revision
            $table->enum('status', ['pending', 'approved', 'rejected', 'replaced'])->default('pending')->after('mime_type');

            // Campos de revision
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('status');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_notes')->nullable()->after('reviewed_at');

            // Indices
            $table->index(['user_case_id', 'document_type']);
            $table->index(['user_case_id', 'status']);
            $table->index('document_type');
            $table->index('status');
        });

        // Copiar datos de path a file_path y size a file_size si existen registros
        DB::statement('UPDATE uploaded_files SET file_path = path, file_size = size WHERE path IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('uploaded_files', function (Blueprint $table) {
            $table->dropForeign(['user_case_id']);
            $table->dropForeign(['uploaded_by']);
            $table->dropForeign(['reviewed_by']);

            $table->dropColumn([
                'user_case_id',
                'uploaded_by',
                'document_type',
                'file_path',
                'file_size',
                'status',
                'reviewed_by',
                'reviewed_at',
                'review_notes',
            ]);
        });
    }
};
