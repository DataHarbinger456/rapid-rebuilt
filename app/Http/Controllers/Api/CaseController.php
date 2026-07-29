<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCaseRequest;
use App\Http\Requests\UpdateCaseRequest;
use App\Models\UserCase;
use App\Models\User;
use App\Services\CaseService;
use App\Services\VINDecoderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CaseController extends Controller
{
    public function __construct(
        protected CaseService $caseService,
        protected VINDecoderService $vinDecoderService
    ) {}

    /**
     * List cases for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->get('status');

        if ($user->isStaff()) {
            $cases = $status
                ? UserCase::byStatus($status)->with(['user', 'vehicle', 'assignedInspector'])->paginate(20)
                : UserCase::with(['user', 'vehicle', 'assignedInspector'])->orderBy('created_at', 'desc')->paginate(20);
        } else {
            $cases = $this->caseService->getCustomerCases($user, $status);
        }

        return response()->json([
            'success' => true,
            'data' => $cases,
        ]);
    }

    /**
     * Create a new case
     */
    public function store(CreateCaseRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Decode VIN if provided
        $vehicleData = [];
        if (!empty($data['vin'])) {
            $decoded = $this->vinDecoderService->decodeForVehicle($data['vin']);
            $vehicleData = array_merge($decoded, $data);
        } else {
            $vehicleData = $data;
        }

        $case = $this->caseService->createCaseWithVehicle($user, $vehicleData, [
            'service_type' => $data['service_type'] ?? 'rebuilt_title',
            'customer_notes' => $data['customer_notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Case created successfully',
            'data' => $case->load(['vehicle', 'user']),
        ], 201);
    }

    /**
     * Get case details
     */
    public function show(Request $request, UserCase $case): JsonResponse
    {
        $user = $request->user();

        // Authorization check
        if (!$user->isStaff() && $case->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $case->load([
            'user',
            'vehicle',
            'assignedInspector',
            'components',
            'inspection',
            'scheduling',
            'timeline' => fn($q) => $q->orderBy('created_at', 'desc')->limit(20),
        ]);

        return response()->json([
            'success' => true,
            'data' => $case,
        ]);
    }

    /**
     * Update case
     */
    public function update(UpdateCaseRequest $request, UserCase $case): JsonResponse
    {
        $user = $request->user();

        // Only owner can update draft cases, staff can update any
        if (!$user->isStaff() && ($case->user_id !== $user->id || $case->status !== 'draft')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized or case cannot be modified',
            ], 403);
        }

        $data = $request->validated();

        if ($case->vehicle && isset($data['vehicle'])) {
            $case->vehicle->update($data['vehicle']);
        }

        if (isset($data['customer_notes'])) {
            $case->update(['customer_notes' => $data['customer_notes']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Case updated successfully',
            'data' => $case->fresh(['vehicle']),
        ]);
    }

    /**
     * Submit case for review
     */
    public function submit(Request $request, UserCase $case): JsonResponse
    {
        $user = $request->user();

        if ($case->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $this->caseService->submitCase($case);

            return response()->json([
                'success' => true,
                'message' => 'Case submitted successfully',
                'data' => $case->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel case
     */
    public function cancel(Request $request, UserCase $case): JsonResponse
    {
        $user = $request->user();
        $reason = $request->input('reason', 'Cancelled by customer');

        if (!$user->isStaff() && $case->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $this->caseService->cancelCase($case, $reason, $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Case cancelled successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get case timeline
     */
    public function timeline(UserCase $case): JsonResponse
    {
        $timeline = $this->caseService->getCaseTimeline($case);

        return response()->json([
            'success' => true,
            'data' => $timeline,
        ]);
    }

    /**
     * Decode VIN
     */
    public function decodeVin(Request $request): JsonResponse
    {
        $request->validate(['vin' => 'required|string|size:17']);

        $vin = strtoupper($request->input('vin'));

        if (!$this->vinDecoderService->isValidFormat($vin)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid VIN format',
            ], 422);
        }

        $decoded = $this->vinDecoderService->decode($vin);

        if (!$decoded) {
            return response()->json([
                'success' => false,
                'message' => 'Could not decode VIN',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $decoded,
        ]);
    }

    /**
     * Get dashboard statistics (staff only)
     */
    public function stats(Request $request): JsonResponse
    {
        if (!$request->user()->isStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $stats = $this->caseService->getDashboardStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Search cases (staff only)
     */
    public function search(Request $request): JsonResponse
    {
        if (!$request->user()->isStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $query = $request->input('q', '');
        $cases = $this->caseService->searchCases($query);

        return response()->json([
            'success' => true,
            'data' => $cases,
        ]);
    }

    /**
     * Assign inspector (staff only)
     */
    public function assignInspector(Request $request, UserCase $case): JsonResponse
    {
        if (!$request->user()->isStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate(['inspector_id' => 'required|exists:users,id']);

        $inspector = User::findOrFail($request->input('inspector_id'));

        try {
            $this->caseService->assignInspector($case, $inspector);

            return response()->json([
                'success' => true,
                'message' => 'Inspector assigned successfully',
                'data' => $case->fresh(['assignedInspector']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update case status (staff only)
     */
    public function updateStatus(Request $request, UserCase $case): JsonResponse
    {
        if (!$request->user()->isStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate(['status' => 'required|string']);

        try {
            $this->caseService->updateStatus($case, $request->input('status'), $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => $case->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
