<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Pricing
            ['key' => 'base_price', 'value' => '150.00', 'group' => 'pricing'],
            ['key' => 'rush_fee', 'value' => '50.00', 'group' => 'pricing'],
            ['key' => 'component_price', 'value' => '10.00', 'group' => 'pricing'],
            ['key' => 'mobile_inspection_fee', 'value' => '75.00', 'group' => 'pricing'],
            ['key' => 'reinspection_fee', 'value' => '50.00', 'group' => 'pricing'],

            // Notifications
            ['key' => 'email_enabled', 'value' => 'true', 'group' => 'notifications'],
            ['key' => 'sms_enabled', 'value' => 'false', 'group' => 'notifications'],
            ['key' => 'reminder_hours_before', 'value' => '24', 'group' => 'notifications'],
            ['key' => 'admin_email', 'value' => 'admin@rapidrebuilt.com', 'group' => 'notifications'],

            // Business
            ['key' => 'business_name', 'value' => 'Rapid Rebuilt LLC', 'group' => 'business'],
            ['key' => 'business_address', 'value' => '123 Main Street, Miami, FL 33101', 'group' => 'business'],
            ['key' => 'business_phone', 'value' => '(305) 555-0100', 'group' => 'business'],
            ['key' => 'business_email', 'value' => 'info@rapidrebuilt.com', 'group' => 'business'],
            ['key' => 'business_hours', 'value' => 'Mon-Fri 9:00 AM - 5:00 PM', 'group' => 'business'],

            // Scheduling
            ['key' => 'appointment_duration', 'value' => '60', 'group' => 'scheduling'],
            ['key' => 'max_appointments_per_day', 'value' => '8', 'group' => 'scheduling'],
            ['key' => 'scheduling_days_ahead', 'value' => '60', 'group' => 'scheduling'],
            ['key' => 'min_reschedule_hours', 'value' => '24', 'group' => 'scheduling'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                ]
            );
        }

        $this->command->info('Settings seeded: ' . count($settings) . ' settings');
    }
}
