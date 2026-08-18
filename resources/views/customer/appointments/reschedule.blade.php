@extends('layouts.customer.master')

@section('title')Reschedule Appointment @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Reschedule Appointment</h3>
        @endslot
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customer.appointments.index') }}">Appointments</a></li>
        <li class="breadcrumb-item active">Reschedule</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Pick a new time</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            Your inspection is currently booked for
                            <strong>{{ $appointment->start->format('l, F j, Y \a\t g:i A') }}</strong>
                            @if($appointment->userCase->vehicle)
                                on your {{ $appointment->userCase->vehicle->year }}
                                {{ $appointment->userCase->vehicle->make }}
                                {{ $appointment->userCase->vehicle->model }}.
                            @endif
                        </p>

                        {{-- Mirrors the validation in processReschedule(): a date
                             after today and a time. The full booking calendar
                             lives on the schedule screen; this is the simple
                             path for one existing appointment. --}}
                        <form action="{{ route('customer.appointments.process-reschedule', $appointment) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="selected_date" class="form-label">New date</label>
                                <input type="date"
                                       id="selected_date"
                                       name="selected_date"
                                       class="form-control @error('selected_date') is-invalid @enderror"
                                       min="{{ now()->addDay()->toDateString() }}"
                                       value="{{ old('selected_date') }}"
                                       required>
                                @error('selected_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="selected_time" class="form-label">New time</label>
                                <select id="selected_time"
                                        name="selected_time"
                                        class="form-select @error('selected_time') is-invalid @enderror"
                                        required>
                                    <option value="" selected disabled>Choose a time</option>
                                    @foreach(['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'] as $slot)
                                        <option value="{{ $slot }}" {{ old('selected_time') === $slot ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::parse($slot)->format('g:i A') }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('selected_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i data-feather="check"></i> Confirm new time
                                </button>
                                <a href="{{ route('customer.appointments.show', $appointment) }}"
                                   class="btn btn-outline-secondary">Keep current time</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
