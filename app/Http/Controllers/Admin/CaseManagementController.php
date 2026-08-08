<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserCase;
use App\Models\User;
use App\Models\CaseTimeline;
use App\Services\CaseService;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CaseManagementController extends Controller
{
    public function __construct(
        protected CaseService $caseService,
        protected DocumentService $documentService
    ) {}

    /**
     * List all cases with filters
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $query = UserCase::with(['user', 'vehicle', 'assignedInspector']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('inspector_id')) {
            $query->where('assigned_inspector_id', $request->input('inspector_id'));
        }

        if ($request->filled('payment_status')) {
            $paymentStatus = $request->input('payment_status');
            if ($paymentStatus === 'paid') {
                $query->where('is_paid', true);
            } elseif ($paymentStatus === 'pending') {
                $query->where('is_paid', false);
            }
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('vehicle', function ($q2) use ($search) {
                      $q2->where('vin', 'like', "%{$search}%")
                         ->orWhere('make', 'like', "%{$search}%")
                         ->orWhere('model', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to') . ' 23:59:59');
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $cases = $query->paginate($request->input('per_page', 20));

        // Get filter options
        $inspectors = User::inspectors()->get();
        $statuses = UserCase::STATUSES;

        $data = [
            'cases' => $cases,
            'inspectors' => $inspectors,
            'statuses' => $statuses,
            'filters' => $request->only(['status', 'inspector_id', 'payment_status', 'search', 'date_from', 'date_to']),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('admin.cases.index', $data);
    }

    /**
     * Show case details
     */
    public function show(Request $request, UserCase $case): View|JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $case->load([
            'user',
            'vehicle',
            'components',
            'documents.extractions',
            'inspection.photos',
            'scheduling',
            'assignedInspector',
            'timeline',
        ]);

        $missingDocuments = $this->documentService->getMissingDocuments($case);

        $data = [
            'case' => $case,
            'missing_documents' => $missingDocuments,
            'available_statuses' => $this->getAvailableStatusTransitions($case->status),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('admin.cases.show', $data);
    }

    /**
     * Update case status
     */
    public function updateStatus(Request $request, UserCase $case): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if (!$user->isStaff()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $request->validate([
            'status' => 'required|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        $newStatus = $request->input('status');
        $oldStatus = $case->status;

        if (!in_array($newStatus, array_keys(UserCase::STATUSES))) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status',
                ], 422);
            }
            return redirect()->back()->with('error', 'Invalid status');
        }

        $this->caseService->forceUpdateStatus($case, $newStatus, $user->id);

        CaseTimeline::log(
            $case->id,
            'status_changed',
            'Status updated by admin',
            "Changed from {$oldStatus} to {$newStatus}" .
                ($request->filled('notes') ? ": {$request->input('notes')}" : ''),
            null,
            $user->id
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => $case->fresh(),
            ]);
        }

        return redirect()->route('admin.cases.show', $case)
            ->with('success', 'Status updated successfully');
    }

    /**
     * Assign inspector to case
     */
    public function assignInspector(Request $request, UserCase $case): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if (!$user->isStaff()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $request->validate([
            'inspector_id' => 'required|exists:users,id',
        ]);

        $inspector = User::findOrFail($request->input('inspector_id'));

        if (!$inspector->isInspector()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected user is not an inspector',
                ], 422);
            }
            return redirect()->back()->with('error', 'Selected user is not an inspector');
        }

        $this->caseService->assignInspector($case, $inspector);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Inspector assigned successfully',
                'data' => $case->fresh()->load('assignedInspector'),
            ]);
        }

        return redirect()->route('admin.cases.show', $case)
            ->with('success', 'Inspector assigned successfully');
    }

    /**
     * Add note to case
     */
    public function addNote(Request $request, UserCase $case): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if (!$user->isStaff()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        CaseTimeline::log(
            $case->id,
            'note_added',
            'Admin note',
            $request->input('note'),
            null,
            $user->id
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Note added successfully',
            ]);
        }

        return redirect()->route('admin.cases.show', $case)
            ->with('success', 'Note added successfully');
    }

    /**
     * Approve an uploaded document from the staff case page.
     */
    public function approveDocument(Request $request, UserCase $case, \App\Models\UploadedFile $document): \Illuminate\Http\RedirectResponse
    {
        abort_unless($document->user_case_id === $case->id, 404);

        $this->documentService->approveDocument($document, $request->user(), $request->input('notes'));

        return back()->with('success', 'Document approved.');
    }

    /**
     * Reject an uploaded document so the customer can re-upload it.
     */
    public function rejectDocument(Request $request, UserCase $case, \App\Models\UploadedFile $document): \Illuminate\Http\RedirectResponse
    {
        abort_unless($document->user_case_id === $case->id, 404);

        $request->validate(['reason' => 'required|string|max:500']);

        $this->documentService->rejectDocument($document, $request->user(), $request->input('reason'));

        return back()->with('success', 'Document rejected. The customer has been asked to re-upload it.');
    }

    /**
     * Bulk update cases
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'case_ids' => 'required|array',
            'case_ids.*' => 'exists:user_cases,id',
            'action' => 'required|in:assign_inspector,update_status',
            'inspector_id' => 'required_if:action,assign_inspector|exists:users,id',
            'status' => 'required_if:action,update_status|string',
        ]);

        $caseIds = $request->input('case_ids');
        $action = $request->input('action');
        $updated = 0;

        foreach ($caseIds as $caseId) {
            $case = UserCase::find($caseId);
            if (!$case) continue;

            if ($action === 'assign_inspector') {
                $inspector = User::find($request->input('inspector_id'));
                if ($inspector && $inspector->isInspector()) {
                    $this->caseService->assignInspector($case, $inspector);
                    $updated++;
                }
            } elseif ($action === 'update_status') {
                $this->caseService->updateStatus($case, $request->input('status'));
                $updated++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Updated {$updated} cases successfully",
        ]);
    }

    /**
     * Export cases
     */
    public function export(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $query = UserCase::with(['user', 'vehicle', 'assignedInspector']);

        // Apply same filters as index
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to') . ' 23:59:59');
        }

        $cases = $query->orderBy('created_at', 'desc')->get();

        $exportData = $cases->map(function ($case) {
            return [
                'case_number' => $case->case_number,
                'status' => $case->status,
                'customer_name' => $case->user?->full_name,
                'customer_email' => $case->user?->email,
                'vehicle_vin' => $case->vehicle?->vin,
                'vehicle_year' => $case->vehicle?->year,
                'vehicle_make' => $case->vehicle?->make,
                'vehicle_model' => $case->vehicle?->model,
                'inspector' => $case->assignedInspector?->full_name,
                'total_amount' => $case->total_amount,
                'is_paid' => $case->is_paid ? 'Paid' : 'Pending',
                'created_at' => $case->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $case->updated_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $exportData,
        ]);
    }

    /**
     * Delete a case
     */
    public function destroy(Request $request, UserCase $case): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $caseNumber = $case->case_number;

        // Delete related records
        $case->timeline()->delete();
        $case->documents()->delete();
        $case->components()->delete();
        if ($case->inspection) {
            $case->inspection->photos()->delete();
            $case->inspection->delete();
        }
        if ($case->scheduling) {
            $case->scheduling->delete();
        }
        $case->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Case {$caseNumber} deleted successfully",
            ]);
        }

        return redirect()->route('admin.cases.index')
            ->with('success', "Case {$caseNumber} deleted successfully");
    }

    /**
     * Get available status transitions
     */
    protected function getAvailableStatusTransitions(string $currentStatus): array
    {
        // Admin can force any status transition
        return array_keys(UserCase::STATUSES);
    }
}
