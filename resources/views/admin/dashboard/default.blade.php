@extends('layouts.admin.master')

@section('title')Dashboard @endsection

@section('css')
<style>
    .stat-card {
        border-radius: 15px;
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .stat-icon svg {
        width: 28px;
        height: 28px;
    }
    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .appointment-item {
        border-left: 4px solid #7366ff;
        padding-left: 15px;
        margin-bottom: 15px;
    }
    .quick-action-btn {
        padding: 15px;
        border-radius: 10px;
        text-align: center;
        transition: all 0.2s;
    }
    .quick-action-btn:hover {
        background: #f8f9fa;
    }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('breadcrumb_title')
        <h3>Dashboard</h3>
    @endslot
    <li class="breadcrumb-item active">Overview</li>
@endcomponent

<div class="container-fluid">
    <!-- Welcome Banner -->
    <div class="row">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="text-white mb-1">Welcome back, {{ auth()->user()->first_name }}!</h4>
                            <p class="mb-0 opacity-75">Here's what's happening with your cases today.</p>
                        </div>
                        <div class="d-none d-md-block">
                            <span class="badge bg-light text-primary fs-6">{{ now()->format('l, F j, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-light-primary text-primary">
                            <i data-feather="briefcase"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0">{{ $stats['total_cases'] ?? 0 }}</h3>
                            <p class="text-muted mb-0">Total Cases</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="badge bg-light-success text-success">{{ $stats['active_cases'] ?? 0 }} active</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-light-warning text-warning">
                            <i data-feather="clock"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0">{{ $stats['pending_review'] ?? 0 }}</h3>
                            <p class="text-muted mb-0">Pending Review</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('admin.cases.index', ['status' => 'submitted']) }}" class="text-warning">View all <i data-feather="arrow-right" style="width: 14px;"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-light-info text-info">
                            <i data-feather="calendar"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0">{{ $stats['scheduled_today'] ?? 0 }}</h3>
                            <p class="text-muted mb-0">Today's Appointments</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('calendar-basic') }}" class="text-info">View calendar <i data-feather="arrow-right" style="width: 14px;"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-light-success text-success">
                            <i data-feather="dollar-sign"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0">US${{ number_format($stats['monthly_revenue'] ?? 0, 2, '.', ',') }}</h3>
                            <p class="text-muted mb-0">Monthly Revenue</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="badge bg-light-success text-success">{{ $stats['completed_this_month'] ?? 0 }} completed</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <a href="{{ route('admin.cases.index') }}" class="quick-action-btn d-block text-center text-decoration-none">
                                <div class="stat-icon bg-light-primary text-primary mx-auto mb-2">
                                    <i data-feather="list"></i>
                                </div>
                                <span class="text-dark">All Cases</span>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="{{ route('admin.settings.users') }}" class="quick-action-btn d-block text-center text-decoration-none">
                                <div class="stat-icon bg-light-success text-success mx-auto mb-2">
                                    <i data-feather="users"></i>
                                </div>
                                <span class="text-dark">Manage Users</span>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="{{ route('admin.reports.index') }}" class="quick-action-btn d-block text-center text-decoration-none">
                                <div class="stat-icon bg-light-warning text-warning mx-auto mb-2">
                                    <i data-feather="bar-chart-2"></i>
                                </div>
                                <span class="text-dark">Reports</span>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="{{ route('admin.settings.index') }}" class="quick-action-btn d-block text-center text-decoration-none">
                                <div class="stat-icon bg-light-secondary text-secondary mx-auto mb-2">
                                    <i data-feather="settings"></i>
                                </div>
                                <span class="text-dark">Settings</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Cases -->
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Recent Cases</h5>
                    <a href="{{ route('admin.cases.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Case #</th>
                                    <th>Customer</th>
                                    <th>Vehicle</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentCases ?? [] as $case)
                                <tr>
                                    <td><strong>{{ $case->case_number }}</strong></td>
                                    <td>{{ $case->user->name ?? 'N/A' }}</td>
                                    <td>
                                        @if($case->vehicle)
                                            {{ $case->vehicle->year }} {{ $case->vehicle->make }} {{ $case->vehicle->model }}
                                        @else
                                            <span class="text-muted">No vehicle</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'draft' => 'secondary',
                                                'submitted' => 'info',
                                                'pending_payment' => 'warning',
                                                'paid' => 'primary',
                                                'pending_documents' => 'warning',
                                                'documents_complete' => 'info',
                                                'pending_scheduling' => 'warning',
                                                'scheduled' => 'primary',
                                                'inspection_in_progress' => 'info',
                                                'passed' => 'success',
                                                'failed' => 'danger',
                                                'completed' => 'success',
                                                'cancelled' => 'danger',
                                            ];
                                            $color = $statusColors[$case->status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $color }}">{{ ucwords(str_replace('_', ' ', $case->status)) }}</span>
                                    </td>
                                    <td>{{ $case->created_at->diffForHumans() }}</td>
                                    <td>
                                        <a href="{{ route('admin.cases.show', $case) }}" class="btn btn-sm btn-light">
                                            <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No cases found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Appointments & Stats -->
        <div class="col-xl-4">
            <!-- Today's Appointments -->
            <div class="card">
                <div class="card-header">
                    <h5>Today's Appointments</h5>
                </div>
                <div class="card-body">
                    @forelse($todayAppointments ?? [] as $appointment)
                    <div class="appointment-item">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $appointment->start->format('h:i A') }}</strong>
                            <span class="badge bg-{{ $appointment->status === 'confirmed' ? 'primary' : 'secondary' }}">
                                {{ ucfirst($appointment->status) }}
                            </span>
                        </div>
                        <p class="mb-1">{{ $appointment->userCase->case_number ?? 'N/A' }}</p>
                        <small class="text-muted">
                            <i data-feather="user" style="width: 12px; height: 12px;"></i>
                            {{ $appointment->user->name ?? 'N/A' }}
                        </small>
                    </div>
                    @empty
                    <div class="text-center py-4">
                        <i data-feather="calendar" style="width: 48px; height: 48px;" class="text-muted mb-2"></i>
                        <p class="text-muted mb-0">No appointments today</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Cases by Status -->
            <div class="card">
                <div class="card-header">
                    <h5>Cases by Status</h5>
                </div>
                <div class="card-body">
                    @php
                        $statusLabels = [
                            'draft' => ['label' => 'Draft', 'color' => 'secondary'],
                            'submitted' => ['label' => 'Submitted', 'color' => 'info'],
                            'pending_payment' => ['label' => 'Pending Payment', 'color' => 'warning'],
                            'paid' => ['label' => 'Paid', 'color' => 'primary'],
                            'scheduled' => ['label' => 'Scheduled', 'color' => 'primary'],
                            'completed' => ['label' => 'Completed', 'color' => 'success'],
                        ];
                        $total = array_sum($casesByStatus ?? []);
                    @endphp
                    @forelse($casesByStatus ?? [] as $status => $count)
                    @php
                        $info = $statusLabels[$status] ?? ['label' => ucwords(str_replace('_', ' ', $status)), 'color' => 'secondary'];
                        $percentage = $total > 0 ? round(($count / $total) * 100) : 0;
                    @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>{{ $info['label'] }}</span>
                            <span class="text-muted">{{ $count }}</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-{{ $info['color'] }}" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted text-center">No data available</p>
                    @endforelse
                </div>
            </div>

            <!-- User Stats -->
            <div class="card">
                <div class="card-header">
                    <h5>User Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-light-primary text-primary me-2" style="width: 40px; height: 40px;">
                                <i data-feather="users" style="width: 18px; height: 18px;"></i>
                            </div>
                            <span>Customers</span>
                        </div>
                        <strong>{{ $stats['total_customers'] ?? 0 }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-light-success text-success me-2" style="width: 40px; height: 40px;">
                                <i data-feather="user-check" style="width: 18px; height: 18px;"></i>
                            </div>
                            <span>Inspectors</span>
                        </div>
                        <strong>{{ $stats['total_inspectors'] ?? 0 }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    // Initialize feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
</script>
@endsection
