<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserCase;
use App\Models\UserFormScheduling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Send each role to its own dashboard. Anything that is not a customer
        // or an inspector falls through to the staff view, so both must be
        // matched explicitly before the staff data below is built.
        if ($user->isCustomer()) {
            return redirect()->route('customer.dashboard');
        }

        if ($user->isInspector()) {
            return redirect()->route('inspector.dashboard');
        }

        abort_unless($user->isStaff(), 403);

        // Admin/Staff Dashboard
        $stats = $this->getAdminStats();
        $recentCases = $this->getRecentCases();
        $todayAppointments = $this->getTodayAppointments();
        $casesByStatus = $this->getCasesByStatus();
        $monthlyRevenue = $this->getMonthlyRevenue();

        return view('admin.dashboard.default', compact(
            'stats',
            'recentCases',
            'todayAppointments',
            'casesByStatus',
            'monthlyRevenue'
        ));
    }

    private function getAdminStats(): array
    {
        return [
            'total_cases' => UserCase::count(),
            'active_cases' => UserCase::whereNotIn('status', ['completed', 'cancelled'])->count(),
            'pending_review' => UserCase::where('status', 'submitted')->count(),
            'scheduled_today' => UserFormScheduling::whereDate('start', today())->count(),
            'total_customers' => User::where('user_type', 'user')->count(),
            'total_inspectors' => User::where('user_type', 'inspector')->count(),
            'monthly_revenue' => UserCase::where('is_paid', true)
                ->whereMonth('paid_at', now()->month)
                ->sum('total_amount'),
            'completed_this_month' => UserCase::where('status', 'completed')
                ->whereMonth('updated_at', now()->month)
                ->count(),
        ];
    }

    private function getRecentCases()
    {
        return UserCase::with(['user', 'vehicle'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    private function getTodayAppointments()
    {
        return UserFormScheduling::with(['userCase', 'user', 'inspector'])
            ->whereDate('start', today())
            ->orderBy('start')
            ->get();
    }

    private function getCasesByStatus(): array
    {
        return UserCase::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    private function getMonthlyRevenue(): array
    {
        $data = UserCase::where('is_paid', true)
            ->whereYear('paid_at', now()->year)
            ->select(
                DB::raw('MONTH(paid_at) as month'),
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $revenue = [];
        for ($i = 1; $i <= 12; $i++) {
            $revenue[$i] = $data[$i] ?? 0;
        }

        return $revenue;
    }
}
