<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserCase;
use App\Models\Vehicle;
use App\Models\Component;
use App\Models\CaseTimeline;
use App\Models\UploadedFile;
use App\Models\UserFormScheduling;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = User::where('user_type', 'user')->get();
        $inspectors = User::where('user_type', 'inspector')->get();

        if ($customers->isEmpty()) {
            $this->command->error('No customers found. Run UserSeeder first.');
            return;
        }

        $statuses = [
            'draft',
            'submitted',
            'pending_payment',
            'paid',
            'pending_documents',
            'documents_complete',
            'pending_scheduling',
            'scheduled',
            'inspection_in_progress',
            'passed',
            'completed',
        ];

        $vehicles = [
            ['year' => 2019, 'make' => 'Honda', 'model' => 'Civic', 'color' => 'white'],
            ['year' => 2020, 'make' => 'Toyota', 'model' => 'Camry', 'color' => 'black'],
            ['year' => 2018, 'make' => 'Ford', 'model' => 'F-150', 'color' => 'red'],
            ['year' => 2021, 'make' => 'Chevrolet', 'model' => 'Silverado', 'color' => 'silver'],
            ['year' => 2017, 'make' => 'BMW', 'model' => '3 Series', 'color' => 'blue'],
            ['year' => 2020, 'make' => 'Mercedes-Benz', 'model' => 'C-Class', 'color' => 'gray'],
            ['year' => 2019, 'make' => 'Nissan', 'model' => 'Altima', 'color' => 'white'],
            ['year' => 2018, 'make' => 'Hyundai', 'model' => 'Sonata', 'color' => 'black'],
        ];

        $partTypes = [
            'engine', 'transmission', 'hood', 'trunk_lid',
            'fender_front_left', 'fender_front_right',
            'door_front_left', 'door_front_right',
            'bumper_front', 'bumper_rear',
            'quarter_panel_left', 'quarter_panel_right'
        ];
        $sourceNames = ['AutoZone', 'O\'Reilly Auto Parts', 'NAPA', 'Advance Auto Parts', 'CarParts.com'];

        $caseNumber = 1000;

        foreach ($customers as $customer) {
            // Create 2-3 cases per customer
            $numCases = rand(2, 3);

            for ($i = 0; $i < $numCases; $i++) {
                $caseNumber++;
                $status = $statuses[array_rand($statuses)];
                $vehicleData = $vehicles[array_rand($vehicles)];
                $isPaid = in_array($status, ['paid', 'scheduled', 'inspection_in_progress', 'passed', 'completed']);

                // Create case
                $inspectionFee = 150.00;
                $serviceFee = rand(50, 200);
                $totalAmount = $inspectionFee + $serviceFee;

                $case = UserCase::create([
                    'user_id' => $customer->id,
                    'case_number' => 'RR-' . date('Y') . '-' . str_pad($caseNumber, 5, '0', STR_PAD_LEFT),
                    'status' => $status,
                    'service_type' => 'rebuilt_title',
                    'inspection_fee' => $inspectionFee,
                    'service_fee' => $serviceFee,
                    'total_amount' => $totalAmount,
                    'is_paid' => $isPaid,
                    'paid_at' => $isPaid ? Carbon::now()->subDays(rand(1, 30)) : null,
                    'submitted_at' => $status !== 'draft' ? Carbon::now()->subDays(rand(1, 60)) : null,
                    'created_at' => Carbon::now()->subDays(rand(1, 60)),
                ]);

                // Create vehicle
                $vin = $this->generateVIN();
                $vehicle = Vehicle::create([
                    'user_case_id' => $case->id,
                    'vin' => $vin,
                    'year' => $vehicleData['year'],
                    'make' => $vehicleData['make'],
                    'model' => $vehicleData['model'],
                    'color' => $vehicleData['color'],
                    'body_type' => 'Sedan',
                    'odometer_reading' => rand(15000, 120000),
                    'title_state' => 'FL',
                ]);

                // Create 2-5 components
                $numComponents = rand(2, 5);
                $usedTypes = [];
                for ($j = 0; $j < $numComponents; $j++) {
                    $partType = $partTypes[array_rand($partTypes)];
                    if (in_array($partType, $usedTypes)) continue;
                    $usedTypes[] = $partType;

                    $conditions = ['new', 'used', 'repaired', 'rebuilt'];
                    Component::create([
                        'user_case_id' => $case->id,
                        'part_type' => $partType,
                        'condition' => $conditions[array_rand($conditions)],
                        'source_name' => $sourceNames[array_rand($sourceNames)],
                        'source_state' => 'FL',
                        'verified' => in_array($status, ['passed', 'completed']),
                    ]);
                }

                // Create timeline entries
                CaseTimeline::create([
                    'user_case_id' => $case->id,
                    'user_id' => $customer->id,
                    'title' => 'Case Created',
                    'description' => 'Rebuilt title case was created',
                    'event_type' => 'case_created',
                    'is_system_event' => true,
                    'created_at' => $case->created_at,
                ]);

                if ($status !== 'draft') {
                    CaseTimeline::create([
                        'user_case_id' => $case->id,
                        'user_id' => $customer->id,
                        'title' => 'Case Submitted',
                        'description' => 'Case was submitted for processing',
                        'event_type' => 'case_submitted',
                        'is_system_event' => true,
                        'created_at' => $case->created_at->addHours(rand(1, 24)),
                    ]);
                }

                // Create appointment for scheduled cases
                if (in_array($status, ['scheduled', 'inspection_in_progress', 'passed', 'completed']) && $inspectors->isNotEmpty()) {
                    $appointmentDate = $status === 'scheduled'
                        ? Carbon::now()->addDays(rand(1, 14))
                        : Carbon::now()->subDays(rand(1, 30));

                    UserFormScheduling::create([
                        'user_case_id' => $case->id,
                        'user_id' => $customer->id,
                        'inspector_id' => $inspectors->random()->id,
                        'start' => $appointmentDate->setHour(rand(9, 15)),
                        'end' => $appointmentDate->copy()->addHour(),
                        'duration_minutes' => 60,
                        'status' => $status === 'scheduled' ? 'confirmed' : 'completed',
                        'location_name' => rand(0, 1) ? 'Office Location' : 'Mobile Inspection',
                        'location_state' => 'FL',
                    ]);
                }
            }
        }

        $this->seedDemoCases($customers, $inspectors, $caseNumber);

        $this->command->info("Cases seeded: " . UserCase::count() . " cases created");
    }

    /**
     * Deterministic demo cases for the demo customer (mike@example.com):
     * one pending_documents case with a rejected doc, and one scheduled case
     * with an upcoming appointment. The demo script relies on these states.
     */
    private function seedDemoCases($customers, $inspectors, int $caseNumber): void
    {
        $demo = $customers->firstWhere('email', 'mike@example.com') ?? $customers->first();

        // --- Case 1: pending_documents with a rejected document ---
        $case1 = UserCase::create([
            'user_id' => $demo->id,
            'case_number' => 'RR-' . date('Y') . '-' . str_pad(++$caseNumber, 5, '0', STR_PAD_LEFT),
            'status' => 'pending_documents',
            'service_type' => 'rebuilt_title',
            'inspection_fee' => 150.00,
            'service_fee' => 100.00,
            'total_amount' => 250.00,
            'is_paid' => true,
            'paid_at' => Carbon::now()->subDays(5),
            'submitted_at' => Carbon::now()->subDays(7),
            'created_at' => Carbon::now()->subDays(7),
        ]);

        Vehicle::create([
            'user_case_id' => $case1->id,
            'vin' => '1HGCV1F30LA123456',
            'year' => 2020,
            'make' => 'Honda',
            'model' => 'Accord',
            'color' => 'blue',
            'body_type' => 'Sedan',
            'odometer_reading' => 48200,
            'title_state' => 'FL',
        ]);

        Component::create([
            'user_case_id' => $case1->id,
            'part_type' => 'hood',
            'condition' => 'used',
            'source_name' => 'AutoZone',
            'source_state' => 'FL',
            'verified' => false,
        ]);

        UploadedFile::create([
            'user_case_id' => $case1->id,
            'user_id' => $demo->id,
            'uploaded_by' => $demo->id,
            'document_type' => 'salvage_title',
            'original_name' => 'salvage-title.pdf',
            'file_path' => "cases/{$case1->id}/documents/salvage-title.pdf",
            'mime_type' => 'application/pdf',
            'file_size' => 245760,
            'status' => 'rejected',
            'rejection_reason' => 'Document is blurry — please re-upload a clear scan of the salvage title.',
        ]);

        CaseTimeline::create([
            'user_case_id' => $case1->id,
            'user_id' => $demo->id,
            'title' => 'Case Created',
            'description' => 'Rebuilt title case was created',
            'event_type' => 'case_created',
            'is_system_event' => true,
            'created_at' => $case1->created_at,
        ]);

        CaseTimeline::create([
            'user_case_id' => $case1->id,
            'user_id' => $demo->id,
            'title' => 'Document Rejected',
            'description' => 'Salvage title was rejected: blurry scan',
            'event_type' => 'document_rejected',
            'is_system_event' => true,
            'created_at' => $case1->created_at->copy()->addDays(2),
        ]);

        // --- Case 2: scheduled with an upcoming appointment ---
        $case2 = UserCase::create([
            'user_id' => $demo->id,
            'case_number' => 'RR-' . date('Y') . '-' . str_pad(++$caseNumber, 5, '0', STR_PAD_LEFT),
            'status' => 'scheduled',
            'service_type' => 'rebuilt_title',
            'inspection_fee' => 150.00,
            'service_fee' => 120.00,
            'total_amount' => 270.00,
            'is_paid' => true,
            'paid_at' => Carbon::now()->subDays(10),
            'submitted_at' => Carbon::now()->subDays(14),
            'created_at' => Carbon::now()->subDays(14),
        ]);

        Vehicle::create([
            'user_case_id' => $case2->id,
            'vin' => '1FTEW1EP5LFA98765',
            'year' => 2021,
            'make' => 'Ford',
            'model' => 'F-150',
            'color' => 'red',
            'body_type' => 'Truck',
            'odometer_reading' => 36500,
            'title_state' => 'FL',
        ]);

        Component::create([
            'user_case_id' => $case2->id,
            'part_type' => 'bumper_front',
            'condition' => 'new',
            'source_name' => 'NAPA',
            'source_state' => 'FL',
            'verified' => false,
        ]);

        foreach ([
            ['case_created', 'Case Created', 'Rebuilt title case was created', 14],
            ['case_submitted', 'Case Submitted', 'Case was submitted for processing', 13],
            ['payment_received', 'Payment Received', 'Payment of $270.00 was received', 10],
            ['appointment_scheduled', 'Inspection Scheduled', 'Inspection appointment was scheduled', 3],
        ] as [$eventType, $title, $description, $daysAgo]) {
            CaseTimeline::create([
                'user_case_id' => $case2->id,
                'user_id' => $demo->id,
                'title' => $title,
                'description' => $description,
                'event_type' => $eventType,
                'is_system_event' => true,
                'created_at' => Carbon::now()->subDays($daysAgo),
            ]);
        }

        if ($inspectors->isNotEmpty()) {
            $start = Carbon::now()->addDays(3)->setTime(10, 0);
            UserFormScheduling::create([
                'user_case_id' => $case2->id,
                'user_id' => $demo->id,
                'inspector_id' => $inspectors->first()->id,
                'start' => $start,
                'end' => $start->copy()->addHour(),
                'duration_minutes' => 60,
                'status' => 'confirmed',
                'location_name' => 'Office Location',
                'location_state' => 'FL',
            ]);
        }
    }

    /**
     * Generate a random valid-looking VIN
     */
    private function generateVIN(): string
    {
        $chars = 'ABCDEFGHJKLMNPRSTUVWXYZ0123456789';
        $vin = '';

        // World Manufacturer Identifier (3 chars)
        $wmis = ['1HG', '2HG', '1FA', '1FT', '1G1', '1N4', '3FA', '5YJ', 'JHM', 'WBA'];
        $vin .= $wmis[array_rand($wmis)];

        // Vehicle Descriptor Section (5 chars)
        for ($i = 0; $i < 5; $i++) {
            $vin .= $chars[rand(0, strlen($chars) - 1)];
        }

        // Check digit (position 9)
        $vin .= rand(0, 9);

        // Year (position 10)
        $years = ['H', 'J', 'K', 'L', 'M', 'N', 'P', 'R'];
        $vin .= $years[array_rand($years)];

        // Plant code (position 11)
        $vin .= $chars[rand(0, strlen($chars) - 1)];

        // Sequential number (6 chars)
        $vin .= str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);

        return $vin;
    }
}
