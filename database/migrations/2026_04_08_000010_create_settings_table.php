<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabla para configuraciones del sistema
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, decimal, boolean, json
            $table->string('group')->default('general');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false); // Si es visible en frontend
            $table->timestamps();

            $table->index('group');
        });

        // Insertar configuraciones iniciales
        $settings = [
            // Precios
            ['key' => 'inspection_fee', 'value' => '40.00', 'type' => 'decimal', 'group' => 'pricing', 'description' => 'Tarifa de inspeccion inicial'],
            ['key' => 'reinspection_fee', 'value' => '20.00', 'type' => 'decimal', 'group' => 'pricing', 'description' => 'Tarifa de re-inspeccion'],
            ['key' => 'service_fee_standard', 'value' => '150.00', 'type' => 'decimal', 'group' => 'pricing', 'description' => 'Tarifa de servicio estandar'],
            ['key' => 'service_fee_expedited', 'value' => '250.00', 'type' => 'decimal', 'group' => 'pricing', 'description' => 'Tarifa de servicio express'],
            ['key' => 'service_fee_dealer', 'value' => '125.00', 'type' => 'decimal', 'group' => 'pricing', 'description' => 'Tarifa de servicio para dealers'],

            // Horarios
            ['key' => 'business_hours_start', 'value' => '08:00', 'type' => 'string', 'group' => 'schedule', 'description' => 'Hora de inicio de operaciones'],
            ['key' => 'business_hours_end', 'value' => '17:00', 'type' => 'string', 'group' => 'schedule', 'description' => 'Hora de fin de operaciones'],
            ['key' => 'appointment_duration', 'value' => '30', 'type' => 'integer', 'group' => 'schedule', 'description' => 'Duracion de cita en minutos'],
            ['key' => 'max_appointments_per_day', 'value' => '16', 'type' => 'integer', 'group' => 'schedule', 'description' => 'Maximo de citas por dia por inspector'],
            ['key' => 'days_in_advance_booking', 'value' => '30', 'type' => 'integer', 'group' => 'schedule', 'description' => 'Dias maximos de anticipacion para agendar'],
            ['key' => 'min_hours_before_booking', 'value' => '24', 'type' => 'integer', 'group' => 'schedule', 'description' => 'Horas minimas de anticipacion'],
            ['key' => 'work_days', 'value' => '["monday","tuesday","wednesday","thursday","friday"]', 'type' => 'json', 'group' => 'schedule', 'description' => 'Dias laborables'],

            // Notificaciones
            ['key' => 'send_reminder_24h', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'description' => 'Enviar recordatorio 24h antes'],
            ['key' => 'send_reminder_1h', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'description' => 'Enviar recordatorio 1h antes'],
            ['key' => 'send_email_on_status_change', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'description' => 'Notificar cambios de estado por email'],
            ['key' => 'send_sms_reminders', 'value' => '0', 'type' => 'boolean', 'group' => 'notifications', 'description' => 'Enviar recordatorios por SMS'],
            ['key' => 'admin_notification_email', 'value' => 'admin@rapidrebuilt.com', 'type' => 'string', 'group' => 'notifications', 'description' => 'Email para notificaciones de admin'],

            // Empresa
            ['key' => 'company_name', 'value' => 'Rapid Rebuilt', 'type' => 'string', 'group' => 'company', 'description' => 'Nombre de la empresa', 'is_public' => true],
            ['key' => 'company_address', 'value' => '', 'type' => 'string', 'group' => 'company', 'description' => 'Direccion de la empresa', 'is_public' => true],
            ['key' => 'company_city', 'value' => 'Tampa', 'type' => 'string', 'group' => 'company', 'description' => 'Ciudad', 'is_public' => true],
            ['key' => 'company_state', 'value' => 'FL', 'type' => 'string', 'group' => 'company', 'description' => 'Estado', 'is_public' => true],
            ['key' => 'company_zip', 'value' => '', 'type' => 'string', 'group' => 'company', 'description' => 'Codigo postal', 'is_public' => true],
            ['key' => 'company_phone', 'value' => '', 'type' => 'string', 'group' => 'company', 'description' => 'Telefono', 'is_public' => true],
            ['key' => 'company_email', 'value' => 'info@rapidrebuilt.com', 'type' => 'string', 'group' => 'company', 'description' => 'Email de contacto', 'is_public' => true],
            ['key' => 'prvip_license_number', 'value' => '', 'type' => 'string', 'group' => 'company', 'description' => 'Numero de licencia PRVIP'],
            ['key' => 'tax_id', 'value' => '', 'type' => 'string', 'group' => 'company', 'description' => 'Tax ID / EIN'],

            // Integraciones
            ['key' => 'stripe_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'integrations', 'description' => 'Stripe habilitado'],
            ['key' => 'twilio_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'integrations', 'description' => 'Twilio SMS habilitado'],
            ['key' => 'vin_decoder_api', 'value' => 'nhtsa', 'type' => 'string', 'group' => 'integrations', 'description' => 'API de decodificacion de VIN'],
        ];

        $now = now();
        foreach ($settings as &$setting) {
            $setting['created_at'] = $now;
            $setting['updated_at'] = $now;
            $setting['is_public'] = $setting['is_public'] ?? false;
        }

        DB::table('settings')->insert($settings);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
