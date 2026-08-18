@extends('layouts.customer.master')

@section('title')Appointment Details @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Appointment Details</h3>
        @endslot
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customer.appointments.index') }}">Appointments</a></li>
        <li class="breadcrumb-item active">Details</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Inspection Appointment</h5>
                        <span class="badge bg-{{
                            [
                                'pending' => 'secondary',
                                'confirmed' => 'success',
                                'in_progress' => 'info',
                                'completed' => 'primary',
                                'cancelled' => 'danger',
                                'rescheduled' => 'warning',
                                'no_show' => 'danger',
                            ][$appointment->status] ?? 'secondary'
                        }}">
                            {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-2 text-center">
                                <div class="bg-light rounded p-3">
                                    <h4 class="mb-0 text-primary">{{ $appointment->start->format('d') }}</h4>
                                    <small class="text-muted">{{ $appointment->start->format('M Y') }}</small>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <h5 class="mb-1">{{ $appointment->start->format('l') }}</h5>
                                <h4 class="text-primary mb-0">{{ $appointment->start->format('g:i A') }}</h4>
                                @if($appointment->end)
                                    <small class="text-muted">Until {{ $appointment->end->format('g:i A') }}</small>
                                @endif
                            </div>
                            <div class="col-md-5">
                                <p class="mb-1"><strong>Case:</strong>
                                    <a href="{{ route('customer.cases.show', $appointment->userCase) }}">
                                        #{{ $appointment->userCase->case_number }}
                                    </a>
                                </p>
                                @if($appointment->userCase->vehicle)
                                    <small class="text-muted">
                                        {{ $appointment->userCase->vehicle->year }}
                                        {{ $appointment->userCase->vehicle->make }}
                                        {{ $appointment->userCase->vehicle->model }}
                                    </small>
                                @endif
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Inspector</strong></p>
                                <p>{{ $appointment->inspector?->full_name ?? 'To be assigned' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Inspection result</strong></p>
                                <p>
                                    @if($appointment->userCase->inspection)
                                        <span class="badge bg-{{
                                            ['passed' => 'success', 'failed' => 'danger', 'in_progress' => 'info'][$appointment->userCase->inspection->result] ?? 'secondary'
                                        }}">{{ ucfirst($appointment->userCase->inspection->result) }}</span>
                                    @else
                                        <span class="text-muted">Not yet inspected</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                    @if($appointment->canReschedule() || $appointment->canCancel())
                        <div class="card-footer d-flex gap-2">
                            @if($appointment->canReschedule())
                                <a href="{{ route('customer.appointments.reschedule', $appointment) }}"
                                   class="btn btn-outline-primary">
                                    <i data-feather="clock"></i> Reschedule
                                </a>
                            @endif
                            @if($appointment->canCancel())
                                <a href="{{ route('customer.appointments.index') }}"
                                   class="btn btn-outline-danger"
                                   onclick="return confirm('To cancel this appointment, please contact support.');">
                                    <i data-feather="x-circle"></i> Cancel
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Need to change something?</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-2">
                            Appointments can be rescheduled while they are still confirmed and
                            have not yet started. If the buttons are missing, the inspection has
                            begun or already happened — contact support and we will sort it out.
                        </p>
                        <a href="{{ route('customer.support') }}" class="btn btn-outline-secondary btn-sm">
                            <i data-feather="life-buoy"></i> Contact support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
