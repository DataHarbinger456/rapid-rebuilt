<?php

namespace App\Services;

use App\Models\Inspection;
use App\Models\InspectionPhoto;
use App\Models\UserCase;
use App\Models\User;
use App\Models\UserFormScheduling;
use App\Models\Component;
use App\Models\CaseTimeline;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InspectionService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Start an inspection from a scheduled appointment
     */
    public function startInspection(UserFormScheduling $scheduling, User $inspector): Inspection
    {
        $case = $scheduling->userCase;

        if (!$inspector->canConductInspections()) {
            throw new \Exception('User is not authorized to conduct inspections');
        }

        if ($case->status !== 'scheduled') {
            throw new \Exception('Case is not in scheduled status');
        }

        return DB::transaction(function () use ($case, $scheduling, $inspector) {
            $inspection = Inspection::create([
                'user_case_id' => $case->id,
                'scheduling_id' => $scheduling->id,
                'inspector_id' => $inspector->id,
                'result' => 'in_progress',
                'started_at' => now(),
                'audit_number' => $this->generateAuditNumber(),
                'region_number' => $inspector->region ?? 'FL',
            ]);

            $case->updateStatus('inspection_in_progress', $inspector->id);

            $scheduling->update(['status' => 'in_progress']);

            CaseTimeline::log(
                $case->id,
                'inspection_started',
                'Inspection started',
                "Inspector {$inspector->full_name} started the inspection",
                ['inspector_id' => $inspector->id]
            );

            return $inspection;
        });
    }

    /**
     * Update inspection checklist data
     */
    public function updateChecklist(Inspection $inspection, array $data): Inspection
    {
        $allowedFields = [
            'vin_verified', 'vin_location_1', 'vin_location_2', 'vin_plates_match', 'vin_no_alterations',
            'odometer_reading_verified', 'odometer_functional',
            'brakes_functional', 'lights_functional', 'steering_functional',
            'horn_functional', 'wipers_functional', 'airbags_present', 'seatbelts_functional',
            'all_receipts_match', 'inspector_notes',
        ];

        $filteredData = array_intersect_key($data, array_flip($allowedFields));

        $inspection->update($filteredData);

        return $inspection->fresh();
    }

    /**
     * Verify a component during inspection
     */
    public function verifyComponent(
        Inspection $inspection,
        Component $component,
        bool $verified,
        ?string $notes = null
    ): void {
        $component->update([
            'verified' => $verified,
            'verification_notes' => $notes,
        ]);

        // Update components_verified JSON
        $componentsVerified = $inspection->components_verified ?? [];
        $componentsVerified[$component->id] = [
            'verified' => $verified,
            'verified_at' => now()->toISOString(),
            'notes' => $notes,
        ];

        $inspection->update(['components_verified' => $componentsVerified]);
    }

    /**
     * Add photo to inspection
     */
    public function addPhoto(
        Inspection $inspection,
        UploadedFile $photo,
        string $type,
        ?array $metadata = null
    ): InspectionPhoto {
        $path = $photo->store(
            "inspections/{$inspection->id}/photos",
            'public'
        );

        return InspectionPhoto::create([
            'inspection_id' => $inspection->id,
            'photo_type' => $type,
            'file_path' => $path,
            'file_name' => $photo->getClientOriginalName(),
            'file_size' => $photo->getSize(),
            'mime_type' => $photo->getMimeType(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Complete inspection with result
     */
    public function completeInspection(Inspection $inspection, string $result, ?string $notes = null): void
    {
        if (!in_array($result, ['passed', 'failed'])) {
            throw new \Exception('Invalid inspection result');
        }

        DB::transaction(function () use ($inspection, $result, $notes) {
            if ($result === 'passed') {
                $inspection->pass($notes);
            } else {
                $inspection->fail($notes ?? 'Inspection failed');
            }

            $case = $inspection->userCase;
            $newStatus = $result === 'passed' ? 'passed' : 'failed';

            $case->updateStatus($newStatus, $inspection->inspector_id);

            $inspection->scheduling->update(['status' => 'completed']);

            CaseTimeline::log(
                $case->id,
                'inspection_completed',
                'Inspection ' . ($result === 'passed' ? 'passed' : 'failed'),
                $notes,
                [
                    'result' => $result,
                    'inspector_id' => $inspection->inspector_id,
                    'duration_minutes' => $inspection->duration_minutes,
                ]
            );

            $this->notificationService->sendInspectionResult($inspection);
        });
    }

    /**
     * Sign inspection (inspector signature)
     */
    public function signInspection(Inspection $inspection, string $signatureBase64): void
    {
        $inspection->sign($signatureBase64);

        CaseTimeline::log(
            $inspection->user_case_id,
            'inspection_signed',
            'Inspector signed inspection report',
            null,
            ['inspector_id' => $inspection->inspector_id]
        );
    }

    /**
     * Get inspection checklist template
     */
    public function getChecklistTemplate(): array
    {
        return Inspection::getChecklistTemplate();
    }

    /**
     * Get required photo types
     */
    public function getRequiredPhotoTypes(): array
    {
        return [
            'vin_plate' => [
                'label' => 'VIN Plate (under windshield)',
                'required' => true,
                'description' => 'Clear photo of VIN plate visible under windshield',
            ],
            'vin_doorjamb' => [
                'label' => 'VIN Door Jamb Sticker',
                'required' => true,
                'description' => 'Photo of VIN sticker on driver door jamb',
            ],
            'odometer' => [
                'label' => 'Odometer Reading',
                'required' => true,
                'description' => 'Clear photo showing current odometer reading',
            ],
            'front_exterior' => [
                'label' => 'Front Exterior',
                'required' => true,
                'description' => 'Full front view of vehicle',
            ],
            'rear_exterior' => [
                'label' => 'Rear Exterior',
                'required' => true,
                'description' => 'Full rear view of vehicle',
            ],
            'left_side' => [
                'label' => 'Left Side',
                'required' => true,
                'description' => 'Full left side view',
            ],
            'right_side' => [
                'label' => 'Right Side',
                'required' => true,
                'description' => 'Full right side view',
            ],
            'engine_bay' => [
                'label' => 'Engine Bay',
                'required' => true,
                'description' => 'Engine compartment showing engine and components',
            ],
            'interior_front' => [
                'label' => 'Interior Front',
                'required' => true,
                'description' => 'Front interior showing dashboard and seats',
            ],
            'interior_rear' => [
                'label' => 'Interior Rear',
                'required' => false,
                'description' => 'Rear interior (if applicable)',
            ],
            'trunk' => [
                'label' => 'Trunk/Cargo Area',
                'required' => false,
                'description' => 'Trunk or cargo area',
            ],
            'component' => [
                'label' => 'Replaced Components',
                'required' => false,
                'description' => 'Photos of major replaced components',
                'multiple' => true,
            ],
        ];
    }

    /**
     * Check if all required photos are uploaded
     */
    public function hasAllRequiredPhotos(Inspection $inspection): bool
    {
        $requiredTypes = collect($this->getRequiredPhotoTypes())
            ->filter(fn($config) => $config['required'])
            ->keys()
            ->toArray();

        $uploadedTypes = $inspection->photos()->pluck('photo_type')->unique()->toArray();

        foreach ($requiredTypes as $type) {
            if (!in_array($type, $uploadedTypes)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get missing required photos
     */
    public function getMissingPhotos(Inspection $inspection): array
    {
        $requiredTypes = collect($this->getRequiredPhotoTypes())
            ->filter(fn($config) => $config['required']);

        $uploadedTypes = $inspection->photos()->pluck('photo_type')->unique()->toArray();

        $missing = [];
        foreach ($requiredTypes as $type => $config) {
            if (!in_array($type, $uploadedTypes)) {
                $missing[$type] = $config['label'];
            }
        }

        return $missing;
    }

    /**
     * Get inspector's today appointments
     */
    public function getInspectorTodayAppointments(User $inspector): Collection
    {
        return UserFormScheduling::where('inspector_id', $inspector->id)
            ->whereDate('scheduled_date', today())
            ->whereIn('status', ['confirmed', 'scheduled'])
            ->with(['userCase.user', 'userCase.vehicle'])
            ->orderBy('scheduled_time')
            ->get();
    }

    /**
     * Get inspector's inspection statistics
     */
    public function getInspectorStats(User $inspector, string $period = 'month'): array
    {
        return $inspector->getInspectionStats($period);
    }

    /**
     * Generate audit number
     */
    protected function generateAuditNumber(): string
    {
        $prefix = 'RR';
        $date = now()->format('ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));

        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Can inspection pass based on current data?
     */
    public function canPass(Inspection $inspection): array
    {
        $issues = [];

        // VIN verification
        if (!$inspection->allVinChecksPassed()) {
            $issues[] = 'VIN verification incomplete or failed';
        }

        // Safety checks
        if (!$inspection->allSafetyChecksPassed()) {
            $issues[] = 'Safety checks incomplete or failed';
        }

        // Odometer
        if (!$inspection->odometer_functional) {
            $issues[] = 'Odometer not verified as functional';
        }

        // Required photos
        if (!$this->hasAllRequiredPhotos($inspection)) {
            $issues[] = 'Missing required photos: ' . implode(', ', $this->getMissingPhotos($inspection));
        }

        // Components verification
        $case = $inspection->userCase;
        $unverifiedComponents = $case->components()
            ->where('verified', false)
            ->count();

        if ($unverifiedComponents > 0) {
            $issues[] = "{$unverifiedComponents} component(s) not verified";
        }

        return [
            'can_pass' => empty($issues),
            'issues' => $issues,
        ];
    }
}
