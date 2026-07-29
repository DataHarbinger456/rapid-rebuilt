<?php

namespace App\Services;

use App\Models\UserCase;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Setting;
use App\Models\CaseTimeline;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CaseService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Create a new case for a customer
     */
    public function createCase(User $customer, array $data = []): UserCase
    {
        return DB::transaction(function () use ($customer, $data) {
            $case = UserCase::create([
                'user_id' => $customer->id,
                'status' => 'draft',
                'service_type' => $data['service_type'] ?? 'rebuilt_title',
                'priority' => $data['priority'] ?? 'normal',
                'inspection_fee' => Setting::inspectionFee(),
                'service_fee' => Setting::serviceFee($data['service_type'] ?? 'standard'),
                'customer_notes' => $data['customer_notes'] ?? null,
            ]);

            $case->calculateTotal();

            CaseTimeline::log(
                $case->id,
                'case_created',
                'Case created',
                'Customer initiated new rebuilt title case'
            );

            return $case;
        });
    }

    /**
     * Create case with vehicle information
     */
    public function createCaseWithVehicle(User $customer, array $vehicleData, array $caseData = []): UserCase
    {
        return DB::transaction(function () use ($customer, $vehicleData, $caseData) {
            $case = $this->createCase($customer, $caseData);

            Vehicle::create([
                'user_case_id' => $case->id,
                'vin' => strtoupper($vehicleData['vin']),
                'year' => $vehicleData['year'],
                'make' => $vehicleData['make'],
                'model' => $vehicleData['model'],
                'title_number' => $vehicleData['title_number'] ?? null,
                'title_state' => $vehicleData['title_state'] ?? 'FL',
                'title_brand' => $vehicleData['title_brand'] ?? 'salvage',
                'body_type' => $vehicleData['body_type'] ?? null,
                'color' => $vehicleData['color'] ?? null,
                'odometer_reading' => $vehicleData['odometer_reading'] ?? null,
                'odometer_status' => $vehicleData['odometer_status'] ?? 'actual',
            ]);

            return $case->fresh(['vehicle']);
        });
    }

    /**
     * Submit case for review
     */
    public function submitCase(UserCase $case): void
    {
        if ($case->status !== 'draft') {
            throw new \Exception('Only draft cases can be submitted');
        }

        if (!$case->vehicle) {
            throw new \Exception('Vehicle information is required');
        }

        $case->submit();

        $this->notificationService->sendCaseSubmitted($case);
    }

    /**
     * Update case status with validation
     */
    public function updateStatus(UserCase $case, string $newStatus, ?int $userId = null): void
    {
        if (!$case->canTransitionTo($newStatus)) {
            throw new \Exception("Cannot transition from {$case->status} to {$newStatus}");
        }

        $case->updateStatus($newStatus, $userId);

        $this->notificationService->sendStatusChanged($case, $newStatus);
    }

    /**
     * Force update case status (for admin use - bypasses transition validation)
     */
    public function forceUpdateStatus(UserCase $case, string $newStatus, ?int $userId = null): void
    {
        $case->updateStatus($newStatus, $userId);

        $this->notificationService->sendStatusChanged($case, $newStatus);
    }

    /**
     * Assign inspector to case
     */
    public function assignInspector(UserCase $case, User $inspector): void
    {
        if (!$inspector->isActiveInspector()) {
            throw new \Exception('User is not an active inspector');
        }

        $case->assignInspector($inspector->id);

        $this->notificationService->sendInspectorAssigned($case, $inspector);
    }

    /**
     * Mark case as paid
     */
    public function markAsPaid(UserCase $case, int $paymentId): void
    {
        $case->markAsPaid($paymentId);

        $this->notificationService->sendPaymentConfirmation($case);
    }

    /**
     * Cancel case
     */
    public function cancelCase(UserCase $case, string $reason, ?int $userId = null): void
    {
        $case->cancel($reason);

        CaseTimeline::log(
            $case->id,
            'case_cancelled',
            'Case cancelled',
            $reason,
            ['cancelled_by' => $userId]
        );

        $this->notificationService->sendCaseCancelled($case);
    }

    /**
     * Calculate and update case total
     */
    public function calculateTotalAmount(UserCase $case): float
    {
        return $case->calculateTotal();
    }

    /**
     * Get customer's cases
     */
    public function getCustomerCases(User $customer, ?string $status = null): Collection
    {
        $query = $customer->cases()->with(['vehicle', 'assignedInspector']);

        if ($status) {
            $query->byStatus($status);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get inspector's assigned cases
     */
    public function getInspectorCases(User $inspector, ?string $status = null): Collection
    {
        $query = UserCase::assignedTo($inspector->id)
            ->with(['user', 'vehicle', 'scheduling']);

        if ($status) {
            $query->byStatus($status);
        }

        return $query->orderBy('scheduled_at', 'asc')->get();
    }

    /**
     * Get cases pending review
     */
    public function getPendingReviewCases(): Collection
    {
        return UserCase::whereIn('status', ['submitted', 'pending_documents', 'documents_complete'])
            ->with(['user', 'vehicle'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get cases ready for inspection
     */
    public function getReadyForInspectionCases(): Collection
    {
        return UserCase::readyForInspection()
            ->with(['user', 'vehicle', 'scheduling'])
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    /**
     * Get unassigned cases
     */
    public function getUnassignedCases(): Collection
    {
        return UserCase::unassigned()
            ->where('is_paid', true)
            ->whereIn('status', ['paid', 'documents_complete', 'pending_scheduling'])
            ->with(['user', 'vehicle'])
            ->orderBy('priority', 'desc')
            ->orderBy('paid_at', 'asc')
            ->get();
    }

    /**
     * Get case timeline
     */
    public function getCaseTimeline(UserCase $case): Collection
    {
        return $case->timeline()->with('user')->get();
    }

    /**
     * Add internal note to case
     */
    public function addInternalNote(UserCase $case, string $note, ?int $userId = null): void
    {
        $case->addNote($note, true);

        CaseTimeline::log(
            $case->id,
            'note_added',
            'Internal note added',
            $note,
            ['internal' => true, 'user_id' => $userId]
        );
    }

    /**
     * Add customer-visible note
     */
    public function addCustomerNote(UserCase $case, string $note): void
    {
        $case->addNote($note, false);
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        return [
            'total_active' => UserCase::active()->count(),
            'pending_payment' => UserCase::unpaid()->active()->count(),
            'scheduled_today' => UserCase::whereDate('scheduled_at', today())->count(),
            'pending_review' => UserCase::whereIn('status', ['submitted', 'pending_documents'])->count(),
            'completed_this_month' => UserCase::completed()
                ->whereMonth('completed_at', now()->month)
                ->count(),
            'revenue_this_month' => UserCase::paid()
                ->whereMonth('paid_at', now()->month)
                ->sum('total_amount'),
            'by_status' => UserCase::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
        ];
    }

    /**
     * Search cases
     */
    public function searchCases(string $query): Collection
    {
        return UserCase::where(function ($q) use ($query) {
            $q->whereHas('user', function ($userQuery) use ($query) {
                $userQuery->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->orWhereHas('vehicle', function ($vehicleQuery) use ($query) {
                $vehicleQuery->where('vin', 'like', "%{$query}%")
                    ->orWhere('make', 'like', "%{$query}%")
                    ->orWhere('model', 'like', "%{$query}%");
            })
            ->orWhere('id', 'like', "%{$query}%");
        })
        ->with(['user', 'vehicle'])
        ->orderBy('created_at', 'desc')
        ->limit(50)
        ->get();
    }
}
