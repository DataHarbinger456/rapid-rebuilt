<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per field the model was asked to read.
 *
 * ai_value and confirmed_value are separate columns on purpose: keeping what
 * the AI said after a human corrects it is the evidence trail behind "nothing
 * reaches a state form unverified", and it makes the per-field correction rate
 * a straight query.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_extraction_fields', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('document_extraction_id');
            $table->foreign('document_extraction_id')
                ->references('id')->on('document_extractions')->onDelete('cascade');

            $table->string('field_key', 50);

            // null ai_value means the model did not find the field, which is a
            // different statement from finding an empty one.
            $table->text('ai_value')->nullable();
            $table->text('confirmed_value')->nullable();

            $table->enum('status', ['pending', 'confirmed', 'corrected', 'rejected'])
                ->default('pending');

            $table->decimal('confidence', 4, 3)->nullable();

            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('confirmed_at')->nullable();

            $table->timestamps();

            $table->unique(['document_extraction_id', 'field_key'], 'extraction_field_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_extraction_fields');
    }
};
