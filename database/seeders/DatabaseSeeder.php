<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UserSeeder::class,
            SettingSeeder::class,
            CaseSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('=================================');
        $this->command->info('Database seeding completed!');
        $this->command->info('=================================');
        $this->command->info('');
        $this->command->info('Test Accounts:');
        $this->command->info('  Admin:     admin@rapidrebuilt.com / password');
        $this->command->info('  Staff:     staff@rapidrebuilt.com / password');
        $this->command->info('  Inspector: john.inspector@rapidrebuilt.com / password');
        $this->command->info('  Customer:  mike@example.com / password');
        $this->command->info('');
    }
}
