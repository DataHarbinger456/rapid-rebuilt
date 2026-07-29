@extends('layouts.admin.master')

@section('title')Admin Dashboard @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Dashboard</h3>
        @endslot
        <li class="breadcrumb-item">Admin</li>
        <li class="breadcrumb-item active">Dashboard</li>
    @endcomponent

    <div class="container-fluid">
        <!-- Stats Row -->
        <div class="row">
            <div class="col-sm-6 col-xl-3 col-lg-6">
                <div class="card o-hidden border-0">
                    <div class="bg-primary b-r-4 card-body">
                        <div class="media static-top-widget">
                            <div class="align-self-center text-center">
                                <i data-feather="briefcase"></i>
                            </div>
                            <div class="media-body">
                                <span class="m-0">Total Cases</span>
                                <h4 class="mb-0 counter">{{ $stats['total_cases'] ?? 0 }}</h4>
                                <i class="icon-bg" data-feather="briefcase"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3 col-lg-6">
                <div class="card o-hidden border-0">
                    <div class="bg-warning b-r-4 card-body">
                        <div class="media static-top-widget">
                            <div class="align-self-center text-center">
                                <i data-feather="activity"></i>
                            </div>
                            <div class="media-body">
                                <span class="m-0">Active Cases</span>
                                <h4 class="mb-0 counter">{{ $stats['active_cases'] ?? 0 }}</h4>
                                <i class="icon-bg" data-feather="activity"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3 col-lg-6">
                <div class="card o-hidden border-0">
                    <div class="bg-success b-r-4 card-body">
                        <div class="media static-top-widget">
                            <div class="align-self-center text-center">
                                <i data-feather="dollar-sign"></i>
                            </div>
                            <div class="media-body">
                                <span class="m-0">Revenue (Month)</span>
                                <h4 class="mb-0">US${{ number_format($stats['revenue_this_month'] ?? 0, 2, '.', ',') }}</h4>
                                <i class="icon-bg" data-feather="dollar-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3 col-lg-6">
                <div class="card o-hidden border-0">
                    <div class="bg-info b-r-4 card-body">
                        <div class="media static-top-widget">
                            <div class="align-self-center text-center">
                                <i data-feather="users"></i>
                            </div>
                            <div class="media-body">
                                <span class="m-0">Total Customers</span>
                                <h4 class="mb-0 counter">{{ $stats['total_users'] ?? 0 }}</h4>
                                <i class="icon-bg" data-feather="users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Actions -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-light-primary rounded-circle p-3">
                                    <i data-feather="file-text" class="text-primary"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-0">{{ $pending_actions['documents_to_review'] ?? 0 }}</h5>
                                <span class="text-muted">Documents to Review</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-light-warning rounded-circle p-3">
                                    <i data-feather="clipboard" class="text-warning"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-0">{{ $pending_actions['inspections_to_review'] ?? 0 }}</h5>
                                <span class="text-muted">Inspections to Review</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-light-danger rounded-circle p-3">
                                    <i data-feather="alert-circle" class="text-danger"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-0">{{ $pending_actions['overdue_cases'] ?? 0 }}</h5>
                                <span class="text-muted">Overdue Cases</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-light-secondary rounded-circle p-3">
                                    <i data-feather="user-plus" class="text-secondary"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-0">{{ $pending_actions['unassigned_cases'] ?? 0 }}</h5>
                                <span class="text-muted">Unassigned Cases</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Today's Appointments -->
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Today's Appointments</h5>
                        <span>{{ now()->format('l, F j, Y') }}</span>
                    </div>
                    <div class="card-body">
                        @forelse($today_appointments as $appointment)
                            <div class="d-flex align-items-center mb-3 p-3 bg-light rounded">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">{{ $appointment->userCase->user->name ?? 'N/A' }}</h6>
                                    <p class="mb-0 text-muted">
                                        {{ \Carbon\Carbon::parse($appointment->start)->format('g:i A') }}
                                        @if($appointment->inspector)
                                            - Inspector: {{ $appointment->inspector->name }}
                                        @endif
                                    </p>
                                </div>
                                <span class="badge badge-{{ $appointment->status === 'confirmed' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($appointment->status) }}
                                </span>
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted">
                                <i data-feather="calendar" style="width: 32px; height: 32px;"></i>
                                <p class="mt-2">No appointments today</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Recent Cases -->
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Cases</h5>
                        <span>Latest case submissions</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Case #</th>
                                        <th>Customer</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recent_cases as $case)
                                        <tr>
                                            <td>
                                                <a href="{{ route('admin.cases.show', $case) }}">
                                                    <strong>{{ $case->case_number }}</strong>
                                                </a>
                                            </td>
                                            <td>{{ $case->user->name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    {{ ucfirst(str_replace('_', ' ', $case->status)) }}
                                                </span>
                                            </td>
                                            <td>{{ $case->created_at->format('M j') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No recent cases</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="{{ route('admin.cases.index') }}" class="btn btn-outline-primary">View All Cases</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Revenue Trend</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Cases Overview</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="casesChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    const revenueData = @json($revenue_chart ?? []);
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: revenueData.map(d => d.month),
            datasets: [{
                label: 'Revenue',
                data: revenueData.map(d => d.revenue),
                borderColor: '#7366ff',
                backgroundColor: 'rgba(115, 102, 255, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => 'US$' + value.toLocaleString('en-US')
                    }
                }
            }
        }
    });

    // Cases Chart
    const casesData = @json($cases_chart ?? []);
    new Chart(document.getElementById('casesChart'), {
        type: 'bar',
        data: {
            labels: casesData.map(d => d.month),
            datasets: [
                {
                    label: 'Created',
                    data: casesData.map(d => d.created),
                    backgroundColor: '#7366ff'
                },
                {
                    label: 'Completed',
                    data: casesData.map(d => d.completed),
                    backgroundColor: '#54ba4a'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
});
</script>
@endpush
