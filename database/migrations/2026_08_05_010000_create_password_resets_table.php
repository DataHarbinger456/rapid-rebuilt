<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backing store for password reset tokens.
 *
 * config/auth.php points the "users" broker at the `password_resets` table,
 * but the table was never created, so Password::sendResetLink() failed.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Guarded: some environments already ship this table from the default
        // Laravel skeleton, and an unguarded create would fail the deploy.
        if (Schema::hasTable('password_resets')) {
            return;
        }

        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_resets');
    }
};
