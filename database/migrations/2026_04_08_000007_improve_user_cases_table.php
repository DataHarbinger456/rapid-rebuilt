<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mejoras a la tabla user_cases para soportar el flujo completo
     */
    public function up(): void
    {
        Schema::table('user_cases', function (Blueprint $table) {
            // Asignacion de inspector
            $table->unsignedBigInteger('assigned_inspector_id')->nullable()->after('user_id');
            $table->foreign('assigned_inspector_id')->references('id')->on('users')->onDelete('set null');

            // Tipo de servicio
            $table->enum('service_type', [
                'rebuilt_title',
                'salvage_inspection',
                'dealer_package',
                'expedited',
                'reinspection'
            ])->default('rebuilt_title')->after('case_status');

            // Precios y totales
            $table->decimal('inspection_fee', 10, 2)->default(40.00)->after('service_type');
            $table->decimal('service_fee', 10, 2)->default(0.00)->after('inspection_fee');
            $table->decimal('reinspection_fee', 10, 2)->default(0.00)->after('service_fee');
            $table->decimal('total_amount', 10, 2)->default(0.00)->after('reinspection_fee');
            $table->boolean('is_paid')->default(false)->after('total_amount');
            $table->unsignedBigInteger('payment_id')->nullable()->after('is_paid');

            // Prioridad
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->after('payment_id');

            // Ubicacion de inspeccion
            $table->string('inspection_location')->nullable()->after('priority');

            // Notas
            $table->text('customer_notes')->nullable()->after('inspection_location');
            $table->text('internal_notes')->nullable()->after('customer_notes');

            // Timestamps importantes
            $table->timestamp('submitted_at')->nullable()->after('internal_notes');
            $table->timestamp('paid_at')->nullable()->after('submitted_at');
            $table->timestamp('scheduled_at')->nullable()->after('paid_at');
            $table->timestamp('inspected_at')->nullable()->after('scheduled_at');
            $table->timestamp('completed_at')->nullable()->after('inspected_at');
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            $table->string('cancellation_reason')->nullable()->after('cancelled_at');

            // Soft deletes
            $table->softDeletes();

            // Indices adicionales
            $table->index('assigned_inspector_id');
            $table->index('service_type');
            $table->index('is_paid');
            $table->index('priority');
        });

        // Actualizar el enum de status si es necesario
        DB::statement("ALTER TABLE `user_cases` MODIFY COLUMN `status` VARCHAR(50) DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_cases', function (Blueprint $table) {
            $table->dropForeign(['assigned_inspector_id']);
            $table->dropColumn([
                'assigned_inspector_id',
                'service_type',
                'inspection_fee',
                'service_fee',
                'reinspection_fee',
                'total_amount',
                'is_paid',
                'payment_id',
                'priority',
                'inspection_location',
                'customer_notes',
                'internal_notes',
                'submitted_at',
                'paid_at',
                'scheduled_at',
                'inspected_at',
                'completed_at',
                'cancelled_at',
                'cancellation_reason',
                'deleted_at'
            ]);
        });
    }
};
