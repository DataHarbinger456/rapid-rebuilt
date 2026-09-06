@extends('layouts.customer.master')

@section('title')Dashboard @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Welcome, {{ auth()->user()->name ?? 'Customer' }}</h3>
        @endslot
        <li class="breadcrumb-item active">Dashboard</li>
    @endcomponent

    <div class="container-fluid">
        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-white">
                                <h4 class="mb-2">Ready to get your rebuilt title?</h4>
                                <p class="mb-0">Start a new case and we'll guide you through the process.</p>
                            </div>
                            <a href="{{ route('customer.cases.create') }}" class="btn btn-light btn-lg">
                                <i data-feather="plus-circle"></i> Start New Case
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card o-hidden">
                    <div class="card-body">
                        <div class="media static-widget">
                            <div class="media-body">
                                <h6 class="font-roboto">Active Cases</h6>
                                <h4 class="mb-0 counter">{{ $activeCases ?? 0 }}</h4>
                            </div>
                            <div class="align-self-center text-center">
                                <i data-feather="briefcase" class="font-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card o-hidden">
                    <div class="card-body">
                        <div class="media static-widget">
                            <div class="media-body">
                                <h6 class="font-roboto">Pending Documents</h6>
                                <h4 class="mb-0 counter">{{ $pendingDocuments ?? 0 }}</h4>
                            </div>
                            <div class="align-self-center text-center">
                                <i data-feather="file-text" class="font-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card o-hidden">
                    <div class="card-body">
                        <div class="media static-widget">
                            <div class="media-body">
                                <h6 class="font-roboto">Upcoming Appointments</h6>
                                <h4 class="mb-0 counter">{{ $upcomingAppointments ?? 0 }}</h4>
                            </div>
                            <div class="align-self-center text-center">
                                <i data-feather="calendar" class="font-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card o-hidden">
                    <div class="card-body">
                        <div class="media static-widget">
                            <div class="media-body">
                                <h6 class="font-roboto">Completed Cases</h6>
                                <h4 class="mb-0 counter">{{ $completedCases ?? 0 }}</h4>
                            </div>
                            <div class="align-self-center text-center">
                                <i data-feather="check-circle" class="font-success"></i>
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
                    <div class="card-header">
                        <h5>My Recent Cases</h5>
                        <a href="{{ route('customer.cases.index') }}" class="btn btn-sm btn-outline-primary pull-right">
                            View All
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Case #</th>
                                        <th>Vehicle</th>
                                        <th>Status</th>
                                        <th>Last Updated</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentCases ?? [] as $case)
                                        <tr>
                                            <td><strong>{{ $case->case_number }}</strong></td>
                                            <td>
                                                @if($case->vehicle)
                                                    {{ $case->vehicle->year }} {{ $case->vehicle->make }} {{ $case->vehicle->model }}
                                                @else
                                                    <span class="text-muted">Not added</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $case->status_color }}">
                                                    {{ $case->status_label }}
                                                </span>
                                            </td>
                                            <td>{{ $case->updated_at->diffForHumans() }}</td>
                                            <td>
                                                <a href="{{ route('customer.cases.show', $case) }}" class="btn btn-sm btn-outline-primary">
                                                    <i data-feather="eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <p class="text-muted mb-2">You don't have any cases yet.</p>
                                                <a href="{{ route('customer.cases.create') }}" class="btn btn-primary">
                                                    Start Your First Case
                                                </a>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Required Actions -->
                @if(isset($requiredActions) && count($requiredActions) > 0)
                    <div class="card">
                        <div class="card-header bg-warning">
                            <h5 class="text-white"><i data-feather="alert-circle"></i> Action Required</h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                @foreach($requiredActions as $action)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{{ $action['title'] }}</strong>
                                            <p class="mb-0 text-muted">{{ $action['description'] }}</p>
                                        </div>
                                        <a href="{{ $action['url'] }}" class="btn btn-sm btn-warning">
                                            {{ $action['button_text'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-xl-4">
                <!-- Next Appointment -->
                @if(isset($nextAppointment))
                    <div class="card">
                        <div class="card-header">
                            <h5>Next Appointment</h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i data-feather="calendar" style="width: 48px; height: 48px;" class="text-primary"></i>
                            </div>
                            <h4>{{ $nextAppointment->start->format('l, F j, Y') }}</h4>
                            <h5 class="text-primary">{{ $nextAppointment->start->format('g:i A') }}</h5>
                            <p class="text-muted mb-3">
                                {{ $nextAppointment->location ?? 'Location TBD' }}
                            </p>
                            <a href="{{ route('customer.appointments.show', $nextAppointment) }}" class="btn btn-outline-primary">
                                View Details
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Recent Notifications -->
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Updates</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse($recentNotifications ?? [] as $notification)
                                <li class="list-group-item">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0 me-3">
                                            <span class="badge badge-light-{{ $notification->type ?? 'primary' }} p-2">
                                                <i data-feather="{{ $notification->icon ?? 'bell' }}"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            {{-- There is no `message` attribute: the columns
                                                 are `title` and `notification`. --}}
                                            <p class="mb-1">{{ $notification->title ?? $notification->notification }}</p>
                                            <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li class="list-group-item text-center text-muted py-4">
                                    No recent updates
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <!-- Help Card -->
                <div class="card">
                    <div class="card-body text-center">
                        <i data-feather="help-circle" style="width: 48px; height: 48px;" class="text-info mb-3"></i>
                        <h5>Need Help?</h5>
                        <p class="text-muted">Our team is ready to assist you with your rebuilt title process.</p>
                        <a href="{{ route('customer.support') ?? '#' }}" class="btn btn-info">
                            Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
