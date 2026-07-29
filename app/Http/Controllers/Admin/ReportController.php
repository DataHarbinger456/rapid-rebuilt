<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserCase;
use App\Models\User;
use App\Models\Inspection;
use App\Models\UserFormScheduling;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Reports dashboard
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

        $data = [
            'available_reports' => $this->getAvailableReports(),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('admin.reports.index', $data);
    }

    /**
     * Revenue report
     */
    public function revenue(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->subMonths(6)->startOfMonth();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now()->endOfMonth();

        $groupBy = $request->input('group_by', 'month');

        $query = UserCase::where('is_paid', true)
            ->whereBetween('paid_at', [$startDate, $endDate]);

        if ($groupBy === 'day') {
            $data = $query->selectRaw('DATE(paid_at) as period, SUM(total_amount) as revenue, COUNT(*) as count')
                ->groupBy('period')
                ->orderBy('period')
                ->get();
        } elseif ($groupBy === 'week') {
            $data = $query->selectRaw('YEARWEEK(paid_at) as period, SUM(total_amount) as revenue, COUNT(*) as count')
                ->groupBy('period')
                ->orderBy('period')
                ->get();
        } else {
            $data = $query->selectRaw('DATE_FORMAT(paid_at, "%Y-%m") as period, SUM(total_amount) as revenue, COUNT(*) as count')
                ->groupBy('period')
                ->orderBy('period')
                ->get();
        }

        $totals = UserCase::where('is_paid', true)
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->selectRaw('SUM(total_amount) as total_revenue, COUNT(*) as total_cases, AVG(total_amount) as average_price')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
                'group_by' => $groupBy,
                'chart_data' => $data,
                'totals' => [
                    'total_revenue' => $totals->total_revenue ?? 0,
                    'total_cases' => $totals->total_cases ?? 0,
                    'average_price' => round($totals->average_price ?? 0, 2),
                ],
            ],
        ]);
    }

    /**
     * Cases report
     */
    public function cases(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->subMonths(6)->startOfMonth();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now()->endOfMonth();

        // Cases by status
        $byStatus = UserCase::selectRaw('status, COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')
            ->get();

        // Cases over time
        $overTime = UserCase::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as period, COUNT(*) as created')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        // Completion rate
        $totalCases = UserCase::whereBetween('created_at', [$startDate, $endDate])->count();
        $completedCases = UserCase::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Average time to completion
        $avgDays = UserCase::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('submitted_at')
            ->selectRaw('AVG(DATEDIFF(updated_at, submitted_at)) as avg_days')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
                'by_status' => $byStatus,
                'over_time' => $overTime,
                'metrics' => [
                    'total_cases' => $totalCases,
                    'completed_cases' => $completedCases,
                    'completion_rate' => $totalCases > 0
                        ? round(($completedCases / $totalCases) * 100, 1)
                        : 0,
                    'average_days_to_completion' => round($avgDays->avg_days ?? 0),
                ],
            ],
        ]);
    }

    /**
     * Inspections report
     */
    public function inspections(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->subMonths(6)->startOfMonth();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now()->endOfMonth();

        // Inspections by result
        $byResult = Inspection::selectRaw('result, COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('result')
            ->get();

        // Inspections by inspector
        $byInspector = Inspection::selectRaw('inspector_id, COUNT(*) as total,
                SUM(CASE WHEN result = "passed" THEN 1 ELSE 0 END) as passed,
                SUM(CASE WHEN result = "failed" THEN 1 ELSE 0 END) as failed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('inspector_id')
            ->groupBy('inspector_id')
            ->with('inspector:id,name')
            ->get();

        // Average inspection duration
        $avgDuration = Inspection::whereIn('result', ['passed', 'failed'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->get()
            ->avg('duration_minutes');

        // Pass rate over time
        $passRateOverTime = Inspection::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as period,
                COUNT(*) as total,
                SUM(CASE WHEN result = "passed" THEN 1 ELSE 0 END) as passed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('result', ['passed', 'failed'])
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function ($item) {
                $item->pass_rate = $item->total > 0
                    ? round(($item->passed / $item->total) * 100, 1)
                    : 0;
                return $item;
            });

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
                'by_result' => $byResult,
                'by_inspector' => $byInspector,
                'pass_rate_over_time' => $passRateOverTime,
                'metrics' => [
                    'average_duration_minutes' => round($avgDuration ?? 0),
                    'total_inspections' => Inspection::whereBetween('created_at', [$startDate, $endDate])->count(),
                ],
            ],
        ]);
    }

    /**
     * Inspector performance report
     */
    public function inspectorPerformance(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->subMonths(3)->startOfMonth();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now()->endOfMonth();

        $inspectors = User::inspectors()
            ->get()
            ->map(function ($inspector) use ($startDate, $endDate) {
                $inspections = Inspection::where('inspector_id', $inspector->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get();

                $completed = $inspections->whereIn('result', ['passed', 'failed']);
                $passed = $inspections->where('result', 'passed');
                $avgDuration = $completed->avg('duration_minutes');

                return [
                    'inspector_id' => $inspector->id,
                    'name' => $inspector->full_name,
                    'total_inspections' => $completed->count(),
                    'passed' => $passed->count(),
                    'failed' => $completed->count() - $passed->count(),
                    'pass_rate' => $completed->count() > 0
                        ? round(($passed->count() / $completed->count()) * 100, 1)
                        : 0,
                    'average_duration_minutes' => round($avgDuration ?? 0),
                    'cases_assigned' => UserCase::where('assigned_inspector_id', $inspector->id)
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->count(),
                ];
            })
            ->sortByDesc('total_inspections')
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
                'inspectors' => $inspectors,
            ],
        ]);
    }

    /**
     * Customer acquisition report
     */
    public function customers(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->subMonths(6)->startOfMonth();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now()->endOfMonth();

        // New customers over time
        $newCustomers = User::customers()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as period, COUNT(*) as count')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        // Repeat customers
        $repeatCustomers = UserCase::selectRaw('user_id, COUNT(*) as case_count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('user_id')
            ->having('case_count', '>', 1)
            ->count();

        $totalCustomers = User::customers()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
                'new_customers_over_time' => $newCustomers,
                'metrics' => [
                    'total_new_customers' => $totalCustomers,
                    'repeat_customers' => $repeatCustomers,
                    'retention_rate' => $totalCustomers > 0
                        ? round(($repeatCustomers / $totalCustomers) * 100, 1)
                        : 0,
                ],
            ],
        ]);
    }

    /**
     * Get available reports list
     */
    protected function getAvailableReports(): array
    {
        return [
            [
                'id' => 'revenue',
                'name' => 'Revenue Report',
                'description' => 'Track revenue over time with payment analytics',
            ],
            [
                'id' => 'cases',
                'name' => 'Cases Report',
                'description' => 'Case volume, status distribution, and completion rates',
            ],
            [
                'id' => 'inspections',
                'name' => 'Inspections Report',
                'description' => 'Inspection results, pass rates, and duration analysis',
            ],
            [
                'id' => 'inspector-performance',
                'name' => 'Inspector Performance',
                'description' => 'Individual inspector metrics and comparisons',
            ],
            [
                'id' => 'customers',
                'name' => 'Customer Report',
                'description' => 'Customer acquisition and retention metrics',
            ],
        ];
    }
}
