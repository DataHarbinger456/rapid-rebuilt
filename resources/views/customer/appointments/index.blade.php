@extends('layouts.customer.master')

@section('title')My Appointments @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>My Appointments</h3>
        @endslot
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Appointments</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row">
            <!-- Upcoming Appointments -->
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Upcoming Appointments</h5>
                    </div>
                    <div class="card-body">
                        @forelse($upcomingAppointments ?? [] as $appointment)
                            <div class="card mb-3 border-left border-primary" style="border-left-width: 4px !important;">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-2 text-center">
                                            <div class="bg-light rounded p-3">
                                                <h4 class="mb-0 text-primary">{{ $appointment->start->format('d') }}</h4>
                                                <small class="text-muted">{{ $appointment->start->format('M Y') }}</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <h5 class="mb-1">{{ $appointment->start->format('l') }}</h5>
                                            <h4 class="text-primary mb-0">{{ $appointment->start->format('g:i A') }}</h4>
                                            @if($appointment->end)
                                                <small class="text-muted">Until {{ $appointment->end->format('g:i A') }}</small>
                                            @endif
                                        </div>
                                        <div class="col-md-3">
                                            <p class="mb-1">
                                                <strong>Case:</strong>
                                                <a href="{{ route('customer.cases.show', $appointment->case) }}">
                                                    #{{ $appointment->case->case_number }}
                                                </a>
                                            </p>
                                            @if($appointment->case->vehicle)
                                                <small class="text-muted">
                                                    {{ $appointment->case->vehicle->year }}
                                                    {{ $appointment->case->vehicle->make }}
                                                    {{ $appointment->case->vehicle->model }}
                                                </small>
                                            @endif
                                        </div>
                                        <div class="col-md-3 text-end">
                                            @if($appointment->canReschedule())
                                                <a href="{{ route('customer.appointments.reschedule', $appointment) }}"
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i data-feather="clock"></i> Reschedule
                                                </a>
                                            @endif
                                            <a href="{{ route('customer.appointments.show', $appointment) }}"
                                               class="btn btn-primary btn-sm">
                                                <i data-feather="eye"></i> Details
                                            </a>
                                        </div>
                                    </div>
                                    @if($appointment->location)
                                        <div class="mt-3 pt-3 border-top">
                                            <i data-feather="map-pin" class="text-muted"></i>
                                            <span>{{ $appointment->location }}</span>
                                            @if($appointment->is_mobile)
                                                <span class="badge badge-info ms-2">Mobile Inspection</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5">
                                <i data-feather="calendar" style="width: 64px; height: 64px;" class="text-muted mb-3"></i>
                                <h4>No Upcoming Appointments</h4>
                                <p class="text-muted">When you schedule an inspection, it will appear here.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Past Appointments -->
                @if(isset($pastAppointments) && $pastAppointments->count() > 0)
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>Past Appointments</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Case</th>
                                        <th>Vehicle</th>
                                        <th>Status</th>
                                        <th>Result</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pastAppointments as $appointment)
                                        <tr>
                                            <td>{{ $appointment->start->format('M j, Y g:i A') }}</td>
                                            <td>
                                                <a href="{{ route('customer.cases.show', $appointment->case) }}">
                                                    #{{ $appointment->case->case_number }}
                                                </a>
                                            </td>
                                            <td>
                                                @if($appointment->case->vehicle)
                                                    {{ $appointment->case->vehicle->year }}
                                                    {{ $appointment->case->vehicle->make }}
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $appointment->status === 'completed' ? 'success' : ($appointment->status === 'cancelled' ? 'danger' : 'secondary') }}">
                                                    {{ ucfirst($appointment->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($appointment->inspection)
                                                    <span class="badge badge-{{ $appointment->inspection->result === 'passed' ? 'success' : 'danger' }}">
                                                        {{ ucfirst($appointment->inspection->result) }}
                                                    </span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-xl-4">
                <!-- Next Appointment Highlight -->
                @if(isset($nextAppointment))
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i data-feather="calendar"></i> Your Next Appointment</h5>
                        </div>
                        <div class="card-body text-center">
                            <h2 class="text-primary">{{ $nextAppointment->start->format('M j') }}</h2>
                            <h4>{{ $nextAppointment->start->format('l') }}</h4>
                            <h3>{{ $nextAppointment->start->format('g:i A') }}</h3>

                            <div class="countdown mt-4" id="countdown">
                                <div class="row">
                                    <div class="col-3">
                                        <h4 id="days">0</h4>
                                        <small>Days</small>
                                    </div>
                                    <div class="col-3">
                                        <h4 id="hours">0</h4>
                                        <small>Hours</small>
                                    </div>
                                    <div class="col-3">
                                        <h4 id="minutes">0</h4>
                                        <small>Mins</small>
                                    </div>
                                    <div class="col-3">
                                        <h4 id="seconds">0</h4>
                                        <small>Secs</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Preparation Checklist -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i data-feather="check-square"></i> Inspection Day Checklist</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i data-feather="check" class="text-success me-2"></i>
                                Vehicle must be clean and accessible
                            </li>
                            <li class="list-group-item">
                                <i data-feather="check" class="text-success me-2"></i>
                                Have all replaced parts receipts ready
                            </li>
                            <li class="list-group-item">
                                <i data-feather="check" class="text-success me-2"></i>
                                Ensure VIN is visible and matches title
                            </li>
                            <li class="list-group-item">
                                <i data-feather="check" class="text-success me-2"></i>
                                Have original title document present
                            </li>
                            <li class="list-group-item">
                                <i data-feather="check" class="text-success me-2"></i>
                                Be available during inspection time
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="card">
                    <div class="card-header">
                        <h5><i data-feather="phone"></i> Need to Change Your Appointment?</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            Please reschedule at least 24 hours in advance to avoid any cancellation fees.
                        </p>
                        <a href="{{ route('customer.support') ?? '#' }}" class="btn btn-outline-primary w-100">
                            <i data-feather="message-circle"></i> Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
@if(isset($nextAppointment))
<script>
// Countdown timer
const appointmentDate = new Date('{{ $nextAppointment->start->toIso8601String() }}');

function updateCountdown() {
    const now = new Date();
    const diff = appointmentDate - now;

    if (diff <= 0) {
        document.getElementById('countdown').innerHTML = '<p class="text-success">Appointment time!</p>';
        return;
    }

    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);

    document.getElementById('days').textContent = days;
    document.getElementById('hours').textContent = hours;
    document.getElementById('minutes').textContent = minutes;
    document.getElementById('seconds').textContent = seconds;
}

updateCountdown();
setInterval(updateCountdown, 1000);
</script>
@endif
@endpush
