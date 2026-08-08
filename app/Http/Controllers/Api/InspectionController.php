<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\UserCase;
use App\Models\UserFormScheduling;
use App\Models\Component;
use App\Services\InspectionService;
use App\Services\PDFGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InspectionController extends Controller
{
    public function __construct(
        protected InspectionService $inspectionService,
        protected PDFGeneratorService $pdfService
    ) {}

    /**
     * Start inspection from appointment
     */
    public function start(Request $request, UserFormScheduling $appointment): JsonResponse
    {
        $user = $request->user();

        if (!$user->canConductInspections()) {
            return response()->json([
                'success' => false,
                'message' => 'Not authorized to conduct inspections',
            ], 403);
        }

        try {
            $inspection = $this->inspectionService->startInspection($appointment, $user);

            return response()->json([
                'success' => true,
                'message' => 'Inspection started',
                'data' => $inspection->load(['userCase.vehicle', 'userCase.components']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get inspection details
     */
    public function show(Request $request, Inspection $inspection): JsonResponse
    {
        $user = $request->user();
        $case = $inspection->userCase;

        if (!$user->isStaff() && !$user->isInspector() && $case->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $inspection->load([
            'userCase.vehicle',
            'userCase.components',
            'userCase.user',
            'inspector',
            'photos',
            'scheduling',
        ]);

        return response()->json([
            'success' => true,
            'data' => $inspection,
        ]);
    }

    /**
     * Update inspection checklist
     */
    public function update(Request $request, Inspection $inspection): JsonResponse
    {
        $user = $request->user();

        if ($inspection->inspector_id !== $user->id && !$user->isStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($inspection->is_completed) {
            return response()->json([
                'success' => false,
                'message' => 'Inspection already completed',
            ], 422);
        }

        // Mirrors the $allowedFields whitelist in
        // InspectionService::updateChecklist(), which filters keys but never
        // checked types. Note that despite their names, vin_location_1/2 are
        // varchar columns holding the VIN as read at each location, and
        // odometer_reading_verified is the integer reading itself, not a flag.
        $validated = $request->validate([
            'vin_verified' => 'sometimes|boolean',
            'vin_location_1' => 'sometimes|nullable|string|max:255',
            'vin_location_2' => 'sometimes|nullable|string|max:255',
            'vin_plates_match' => 'sometimes|boolean',
            'vin_no_alterations' => 'sometimes|boolean',
            'odometer_reading_verified' => 'sometimes|nullable|integer|min:0',
            'odometer_functional' => 'sometimes|boolean',
            'brakes_functional' => 'sometimes|boolean',
            'lights_functional' => 'sometimes|boolean',
            'steering_functional' => 'sometimes|boolean',
            'horn_functional' => 'sometimes|boolean',
            'wipers_functional' => 'sometimes|boolean',
            'airbags_present' => 'sometimes|boolean',
            'seatbelts_functional' => 'sometimes|boolean',
            'all_receipts_match' => 'sometimes|boolean',
            'inspector_notes' => 'sometimes|nullable|string|max:5000',
        ]);

        try {
            $inspection = $this->inspectionService->updateChecklist($inspection, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Checklist updated',
                'data' => $inspection,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Upload inspection photo
     */
    public function uploadPhoto(Request $request, Inspection $inspection): JsonResponse
    {
        $user = $request->user();

        if ($inspection->inspector_id !== $user->id && !$user->isStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'photo' => 'required|image|max:10240', // 10MB max
            'type' => 'required|string',
            'metadata' => 'nullable|array',
        ]);

        try {
            $photo = $this->inspectionService->addPhoto(
                $inspection,
                $request->file('photo'),
                $request->input('type'),
                $request->input('metadata')
            );

            return response()->json([
                'success' => true,
                'message' => 'Photo uploaded',
                'data' => $photo,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Verify a component
     */
    public function verifyComponent(Request $request, Inspection $inspection, Component $component): JsonResponse
    {
        $user = $request->user();

        if ($inspection->inspector_id !== $user->id && !$user->isStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'verified' => 'required|boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->inspectionService->verifyComponent(
                $inspection,
                $component,
                $request->boolean('verified'),
                $request->input('notes')
            );

            return response()->json([
                'success' => true,
                'message' => 'Component verification updated',
                'data' => $component->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Complete inspection
     */
    public function complete(Request $request, Inspection $inspection): JsonResponse
    {
        $user = $request->user();

        if ($inspection->inspector_id !== $user->id && !$user->isStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'result' => 'required|in:passed,failed',
            'notes' => 'nullable|string',
        ]);

        // Check if can pass
        if ($request->input('result') === 'passed') {
            $canPass = $this->inspectionService->canPass($inspection);
            if (!$canPass['can_pass']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inspection cannot pass',
                    'issues' => $canPass['issues'],
                ], 422);
            }
        }

        try {
            $this->inspectionService->completeInspection(
                $inspection,
                $request->input('result'),
                $request->input('notes')
            );

            return response()->json([
                'success' => true,
                'message' => 'Inspection completed',
                'data' => $inspection->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Sign inspection
     */
    public function sign(Request $request, Inspection $inspection): JsonResponse
    {
        $user = $request->user();

        if ($inspection->inspector_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only assigned inspector can sign',
            ], 403);
        }

        $request->validate([
            'signature' => 'required|string', // Base64 encoded
        ]);

        try {
            $this->inspectionService->signInspection($inspection, $request->input('signature'));

            return response()->json([
                'success' => true,
                'message' => 'Inspection signed',
                'data' => $inspection->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Generate documents after passed inspection
     */
    public function generateDocuments(Request $request, Inspection $inspection): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff() && $inspection->inspector_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($inspection->result !== 'passed') {
            return response()->json([
                'success' => false,
                'message' => 'Can only generate documents for passed inspections',
            ], 422);
        }

        $case = $inspection->userCase;

        try {
            $packagePath = $this->pdfService->generateFullPackage($case);

            return response()->json([
                'success' => true,
                'message' => 'Documents generated successfully',
                'data' => [
                    'package_path' => $packagePath,
                    'documents' => $this->pdfService->getGeneratedDocuments($case),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get checklist template
     */
    public function checklistTemplate(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'checklist' => $this->inspectionService->getChecklistTemplate(),
                'required_photos' => $this->inspectionService->getRequiredPhotoTypes(),
            ],
        ]);
    }

    /**
     * Check if inspection can pass
     */
    public function canPass(Inspection $inspection): JsonResponse
    {
        $result = $this->inspectionService->canPass($inspection);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get missing required photos
     */
    public function missingPhotos(Inspection $inspection): JsonResponse
    {
        $missing = $this->inspectionService->getMissingPhotos($inspection);

        return response()->json([
            'success' => true,
            'data' => [
                'missing' => $missing,
                'all_uploaded' => empty($missing),
            ],
        ]);
    }
}
