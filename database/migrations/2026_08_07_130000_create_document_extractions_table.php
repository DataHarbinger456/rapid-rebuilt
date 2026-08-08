<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per attempt to read an uploaded document with a vision model.
 *
 * provider and model are stored per row rather than read from config at
 * display time: changing config must not rewrite the history of what actually
 * read a document. raw_payload is encrypted at the application layer because a
 * salvage title carries the owner's name and address.
 *
 * Re-running an extraction marks the prior row 'superseded' instead of
 * deleting it, so the audit trail survives.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_extractions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('uploaded_file_id');
            $table->foreign('uploaded_file_id')->references('id')->on('uploaded_files')->onDelete('cascade');

            // Denormalised from uploaded_files so case-scoped lookups and the
            // staff review route do not need a join.
            $table->unsignedBigInteger('user_case_id');
            $table->foreign('user_case_id')->references('id')->on('user_cases')->onDelete('cascade');

            $table->string('document_type', 50);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'superseded'])
                ->default('pending');

            $table->string('provider', 50);
            $table->string('model', 100);

            // Laravel 'encrypted:array' cast — ciphertext, so it cannot be a
            // JSON column.
            $table->text('raw_payload')->nullable();

            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['uploaded_file_id', 'status']);
            $table->index(['user_case_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_extractions');
    }
};
