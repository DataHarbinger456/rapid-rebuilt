@extends('layouts.admin.master')

@section('title')Inspector Dashboard @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Inspector Dashboard</h3>
        @endslot
        <li class="breadcrumb-item">Inspector</li>
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
                                <i data-feather="calendar"></i>
                            </div>
                            <div class="media-body">
                                <span class="m-0">Today's Appointments</span>
                                <h4 class="mb-0 counter">{{ $today_appointments->count() }}</h4>
                                <i class="icon-bg" data-feather="calendar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3 col-lg-6">
                <div class="card o-hidden border-0">
                    <div class="bg-secondary b-r-4 card-body">
                        <div class="media static-top-widget">
                            <div class="align-self-center text-center">
                                <i data-feather="clock"></i>
                            </div>
                            <div class="media-body">
                                <span class="m-0">Upcoming</span>
                                <h4 class="mb-0 counter">{{ $upcoming_count }}</h4>
                                <i class="icon-bg" data-feather="clock"></i>
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
                                <i data-feather="check-circle"></i>
                            </div>
                            <div class="media-body">
                                <span class="m-0">Pass Rate</span>
                                <h4 class="mb-0">{{ $pass_rate }}%</h4>
                                <i class="icon-bg" data-feather="check-circle"></i>
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
                                <i data-feather="file-text"></i>
                            </div>
                            <div class="media-body">
                                <span class="m-0">Completed (Month)</span>
                                <h4 class="mb-0 counter">{{ $stats['total'] ?? 0 }}</h4>
                                <i class="icon-bg" data-feather="file-text"></i>
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
                                        <i data-feather="clock" class="me-1" style="width: 14px;"></i>
                                        {{ \Carbon\Carbon::parse($appointment->start)->format('g:i A') }}
                                        @if($appointment->userCase->vehicle)
                                            - {{ $appointment->userCase->vehicle->year }} {{ $appointment->userCase->vehicle->make }} {{ $appointment->userCase->vehicle->model }}
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <span class="badge badge-{{ $appointment->status === 'confirmed' ? 'success' : ($appointment->status === 'in_progress' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                                    </span>
                                </div>
                                <div class="ms-3">
                                    <a href="{{ route('inspector.checklist', $appointment) }}" class="btn btn-primary btn-sm">
                                        <i data-feather="clipboard"></i> Inspect
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <i data-feather="calendar" style="width: 48px; height: 48px; color: #ccc;"></i>
                                <p class="text-muted mt-2">No appointments scheduled for today</p>
                            </div>
                        @endforelse

                        @if($today_appointments->count() > 0)
                            <div class="text-center mt-3">
                                <a href="{{ route('inspector.agenda') }}" class="btn btn-outline-primary">View Full Agenda</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Assigned Cases -->
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Assigned Cases</h5>
                        <span>Pending inspections</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Case #</th>
                                        <th>Customer</th>
                                        <th>Vehicle</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($assigned_cases as $case)
                                        <tr>
                                            <td>
                                                <strong>{{ $case->case_number }}</strong>
                                            </td>
                                            <td>{{ $case->user->name ?? 'N/A' }}</td>
                                            <td>
                                                @if($case->vehicle)
                                                    {{ $case->vehicle->year }} {{ $case->vehicle->make }}
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $case->status === 'scheduled' ? 'primary' : 'secondary' }}">
                                                    {{ ucfirst(str_replace('_', ' ', $case->status)) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No assigned cases</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Inspections -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Inspections</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Vehicle</th>
                                        <th>Result</th>
                                        <th>Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recent_inspections as $inspection)
                                        <tr>
                                            <td>{{ $inspection->completed_at ? $inspection->completed_at->format('M j, Y') : 'In Progress' }}</td>
                                            <td>
                                                @if($inspection->userCase->vehicle)
                                                    {{ $inspection->userCase->vehicle->year }} {{ $inspection->userCase->vehicle->make }} {{ $inspection->userCase->vehicle->model }}
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($inspection->result === 'passed')
                                                    <span class="badge badge-success">Passed</span>
                                                @elseif($inspection->result === 'failed')
                                                    <span class="badge badge-danger">Failed</span>
                                                @else
                                                    <span class="badge badge-warning">Pending</span>
                                                @endif
                                            </td>
                                            <td>{{ $inspection->duration_minutes ?? '-' }} min</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No recent inspections</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($recent_inspections->count() > 0)
                            <div class="text-center mt-3">
                                <a href="{{ route('inspector.history') }}" class="btn btn-outline-primary">View All History</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
