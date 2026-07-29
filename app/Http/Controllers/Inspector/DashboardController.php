<?php

namespace App\Http\Controllers\Inspector;

use App\Http\Controllers\Controller;
use App\Models\UserCase;
use App\Models\UserFormScheduling;
use App\Models\Inspection;
use App\Services\AppointmentService;
use App\Services\InspectionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected AppointmentService $appointmentService,
        protected InspectionService $inspectionService
    ) {}

    /**
     * Inspector dashboard view
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();

        if (!$user->isInspector()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $todayAppointments = $this->appointmentService->getInspectorSchedule($user, now());
        $upcomingCount = $this->appointmentService->getUpcomingCount($user);
        $stats = $this->inspectionService->getInspectorStats($user, 'month');

        $assignedCases = UserCase::assignedTo($user->id)
            ->active()
            ->with(['user', 'vehicle'])
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get();

        $recentInspections = Inspection::byInspector($user->id)
            ->with(['userCase.vehicle'])
            ->orderBy('completed_at', 'desc')
            ->limit(5)
            ->get();

        $data = [
            'today_appointments' => $todayAppointments,
            'upcoming_count' => $upcomingCount,
            'stats' => $stats,
            'assigned_cases' => $assignedCases,
            'recent_inspections' => $recentInspections,
            'pass_rate' => $stats['total'] > 0
                ? round(($stats['passed'] / $stats['total']) * 100, 1)
                : 0,
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('inspector.dashboard', $data);
    }

    /**
     * Get inspector statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $period = $request->input('period', 'month');

        $stats = $this->inspectionService->getInspectorStats($user, $period);

        // Additional stats
        $totalCompleted = Inspection::byInspector($user->id)
            ->whereIn('result', ['passed', 'failed'])
            ->count();

        $averageDuration = Inspection::byInspector($user->id)
            ->whereIn('result', ['passed', 'failed'])
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->get()
            ->avg('duration_minutes');

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'stats' => $stats,
                'total_completed' => $totalCompleted,
                'average_duration_minutes' => round($averageDuration ?? 0),
                'pass_rate' => $stats['total'] > 0
                    ? round(($stats['passed'] / $stats['total']) * 100, 1)
                    : 0,
            ],
        ]);
    }
}
