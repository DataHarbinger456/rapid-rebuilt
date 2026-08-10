<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserCase;
use App\Models\User;
use App\Models\Inspection;
use App\Models\UserFormScheduling;
use App\Services\CaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected CaseService $caseService
    ) {}

    /**
     * Admin dashboard view
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

        $stats = $this->getOverviewStats();
        $recentCases = $this->getRecentCases();
        $todayAppointments = $this->getTodayAppointments();
        $pendingActions = $this->getPendingActions();

        $data = [
            'stats' => $stats,
            'recent_cases' => $recentCases,
            'today_appointments' => $todayAppointments,
            'pending_actions' => $pendingActions,
            'revenue_chart' => $this->getRevenueChart(),
            'cases_chart' => $this->getCasesChart(),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('admin.dashboard', $data);
    }

    /**
     * Get overview statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $period = $request->input('period', 'month');
        $stats = $this->caseService->getDashboardStats($period);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get overview stats
     */
    protected function getOverviewStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            'total_cases' => UserCase::count(),
            'active_cases' => UserCase::active()->count(),
            'cases_this_month' => UserCase::where('created_at', '>=', $thisMonth)->count(),
            'completed_this_month' => UserCase::where('status', 'completed')
                ->where('updated_at', '>=', $thisMonth)
                ->count(),
            'total_users' => User::where('user_type', 'user')->count(),
            'total_inspectors' => User::where('user_type', 'inspector')->count(),
            'inspections_today' => Inspection::whereDate('started_at', $today)->count(),
            'pending_documents' => UserCase::where('status', 'pending_documents')->count(),
            'pending_scheduling' => UserCase::where('status', 'pending_scheduling')->count(),
            'pending_review' => UserCase::where('status', 'pending_review')->count(),
            'revenue_this_month' => UserCase::where('is_paid', true)
                ->where('paid_at', '>=', $thisMonth)
                ->sum('total_amount'),
        ];
    }

    /**
     * Get recent cases
     */
    protected function getRecentCases()
    {
        return UserCase::with(['user', 'vehicle', 'assignedInspector'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get today's appointments
     */
    protected function getTodayAppointments()
    {
        return UserFormScheduling::whereDate('start', Carbon::today())
            ->with(['userCase.user', 'userCase.vehicle', 'inspector'])
            ->orderBy('start')
            ->get();
    }

    /**
     * Get pending actions that need attention
     */
    protected function getPendingActions(): array
    {
        return [
            'documents_to_review' => UserCase::whereHas('documents', function ($q) {
                $q->where('status', 'pending');
            })->count(),
            'inspections_to_review' => Inspection::where('result', 'pending')
                ->whereNotNull('completed_at')
                ->count(),
            'overdue_cases' => UserCase::active()
                ->where('created_at', '<', Carbon::now()->subDays(30))
                ->count(),
            'unassigned_cases' => UserCase::whereNull('assigned_inspector_id')
                ->whereIn('status', ['scheduled', 'pending_scheduling'])
                ->count(),
        ];
    }

    /**
     * Get revenue chart data
     */
    protected function getRevenueChart(): array
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $revenue = UserCase::where('is_paid', true)
                ->whereYear('paid_at', $month->year)
                ->whereMonth('paid_at', $month->month)
                ->sum('total_amount');

            $data[] = [
                'month' => $month->format('M Y'),
                'revenue' => $revenue,
            ];
        }
        return $data;
    }

    /**
     * Get cases chart data
     */
    protected function getCasesChart(): array
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $created = UserCase::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            $completed = UserCase::where('status', 'completed')
                ->whereYear('updated_at', $month->year)
                ->whereMonth('updated_at', $month->month)
                ->count();

            $data[] = [
                'month' => $month->format('M Y'),
                'created' => $created,
                'completed' => $completed,
            ];
        }
        return $data;
    }

    /**
     * Activity feed
     */
    public function activity(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $limit = $request->input('limit', 20);

        $activities = \App\Models\CaseTimeline::with(['userCase.user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($timeline) {
                return [
                    'id' => $timeline->id,
                    'case_id' => $timeline->user_case_id,
                    'case_number' => $timeline->userCase?->case_number,
                    'user_name' => $timeline->userCase?->user?->name,
                    'event' => $timeline->event,
                    'title' => $timeline->title,
                    'description' => $timeline->description,
                    'created_at' => $timeline->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }
}
