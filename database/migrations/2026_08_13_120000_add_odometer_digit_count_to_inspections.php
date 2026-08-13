<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How many digits the odometer physically displays, as recorded by the
     * inspector standing in front of the car.
     *
     * Three HSMV forms ask this and none of our data answers it: the title
     * never states it, and the reading cannot imply it because 62,712 miles
     * looks identical on a five- and a six-digit cluster. Until now it was
     * inferred from the model year, which is a good guess and still only a
     * guess on a declaration sworn under penalty of perjury.
     *
     * Nullable because the inspection may not have happened yet, and because
     * every existing row predates the question. A null keeps the old model-year
     * fallback, so nothing regresses.
     */
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->unsignedTinyInteger('odometer_digit_count')
                ->nullable()
                ->after('odometer_functional');
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumn('odometer_digit_count');
        });
    }
};
