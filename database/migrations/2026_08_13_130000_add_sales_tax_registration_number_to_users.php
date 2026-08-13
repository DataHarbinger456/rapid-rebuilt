<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The dealer's own Florida sales tax registration number.
     *
     * Section 7 of the HSMV 82040 asks for it beside the dealer licence
     * number, and it belongs to the dealer filing the application, not to
     * Rapid Rebuilt: two dealers submitting through this portal have two
     * different numbers. It was previously read from
     * settings.prvip_license_number, a single company-wide value that is also
     * empty, so the box never printed anything.
     *
     * Nullable and untouched for every other user type. A private customer has
     * no sales tax registration, and their Section 7 stays blank.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('sales_tax_registration_number')
                ->nullable()
                ->after('license_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sales_tax_registration_number');
        });
    }
};
