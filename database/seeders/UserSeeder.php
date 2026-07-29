<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultFields = [
            'address_1' => '',
            'city' => 'Miami',
            'states' => 'FL',
            'zip' => '33101',
        ];

        // Admin user
        User::updateOrCreate(
            ['email' => 'admin@rapidrebuilt.com'],
            array_merge($defaultFields, [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'username' => 'rr.admin',
                'password' => Hash::make('password'),
                'user_type' => 'admin',
                'phone' => '305-555-0001',
                'email_verified_at' => now(),
            ])
        );

        // Staff user
        User::updateOrCreate(
            ['email' => 'staff@rapidrebuilt.com'],
            array_merge($defaultFields, [
                'first_name' => 'Staff',
                'last_name' => 'Member',
                'username' => 'rr.staff',
                'password' => Hash::make('password'),
                'user_type' => 'staff',
                'phone' => '305-555-0002',
                'email_verified_at' => now(),
            ])
        );

        // Inspector users
        $inspectors = [
            [
                'first_name' => 'John',
                'last_name' => 'Inspector',
                'username' => 'john.inspector',
                'email' => 'john.inspector@rapidrebuilt.com',
                'phone' => '305-555-0101',
                'inspector_license' => 'INS-001',
                'inspector_active' => true,
            ],
            [
                'first_name' => 'Maria',
                'last_name' => 'Inspector',
                'username' => 'maria.inspector',
                'email' => 'maria.inspector@rapidrebuilt.com',
                'phone' => '305-555-0102',
                'inspector_license' => 'INS-002',
                'inspector_active' => true,
            ],
            [
                'first_name' => 'Carlos',
                'last_name' => 'Inspector',
                'username' => 'carlos.inspector',
                'email' => 'carlos.inspector@rapidrebuilt.com',
                'phone' => '305-555-0103',
                'inspector_license' => 'INS-003',
                'inspector_active' => true,
            ],
        ];

        foreach ($inspectors as $inspector) {
            User::updateOrCreate(
                ['email' => $inspector['email']],
                array_merge($defaultFields, [
                    'first_name' => $inspector['first_name'],
                    'last_name' => $inspector['last_name'],
                    'username' => $inspector['username'],
                    'password' => Hash::make('password'),
                    'user_type' => 'inspector',
                    'phone' => $inspector['phone'],
                    'inspector_license' => $inspector['inspector_license'],
                    'inspector_active' => $inspector['inspector_active'],
                    'inspector_certification_date' => now()->subYear(),
                    'email_verified_at' => now(),
                ])
            );
        }

        // Customer users
        $customers = [
            [
                'first_name' => 'Mike',
                'last_name' => 'Customer',
                'username' => 'mike.customer',
                'email' => 'mike@example.com',
                'phone' => '305-555-1001',
            ],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Customer',
                'username' => 'sarah.customer',
                'email' => 'sarah@example.com',
                'phone' => '305-555-1002',
            ],
            [
                'first_name' => 'David',
                'last_name' => 'Customer',
                'username' => 'david.customer',
                'email' => 'david@example.com',
                'phone' => '305-555-1003',
            ],
            [
                'first_name' => 'Emily',
                'last_name' => 'Customer',
                'username' => 'emily.customer',
                'email' => 'emily@example.com',
                'phone' => '305-555-1004',
            ],
            [
                'first_name' => 'Robert',
                'last_name' => 'Customer',
                'username' => 'robert.customer',
                'email' => 'robert@example.com',
                'phone' => '305-555-1005',
            ],
        ];

        foreach ($customers as $customer) {
            User::updateOrCreate(
                ['email' => $customer['email']],
                array_merge($defaultFields, [
                    'first_name' => $customer['first_name'],
                    'last_name' => $customer['last_name'],
                    'username' => $customer['username'],
                    'password' => Hash::make('password'),
                    'user_type' => 'user',
                    'phone' => $customer['phone'],
                    'email_verified_at' => now(),
                ])
            );
        }

        $this->command->info('Users seeded: 1 admin, 1 staff, 3 inspectors, 5 customers');
    }
}
