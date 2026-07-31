<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Bootstrap seeder for a fresh production instance.
 *
 * Seeds reference data only: business/pricing settings and a single staff
 * account. No demo cases, customers, or inspectors.
 *
 * The staff password comes from SEED_STAFF_PASSWORD; when unset, a random
 * one is generated and printed exactly once so nothing guessable ships.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SettingSeeder::class);

        $email = env('SEED_STAFF_EMAIL', 'staff@rapidrebuilt.com');
        $password = env('SEED_STAFF_PASSWORD');
        $generated = false;

        if (! $password) {
            $password = Str::password(20);
            $generated = true;
        }

        $staff = User::firstOrCreate(
            ['email' => $email],
            [
                'first_name' => 'Staff',
                'last_name' => 'Member',
                'username' => 'rr.staff',
                'password' => Hash::make($password),
                'user_type' => 'staff',
                'phone' => '',
                'address_1' => '',
                'city' => '',
                'states' => '',
                'zip' => '',
                'email_verified_at' => now(),
            ]
        );

        if ($staff->wasRecentlyCreated) {
            $this->command->info("Staff account created: {$email}");
            if ($generated) {
                $this->command->warn("Generated password (store it now, shown once): {$password}");
            }
        } else {
            $this->command->info("Staff account already exists: {$email} (unchanged)");
        }
    }
}
