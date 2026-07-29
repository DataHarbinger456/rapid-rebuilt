@extends('layouts.admin.master')

@section('title')Inspector Agenda @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Agenda</h3>
        @endslot
        <li class="breadcrumb-item">Inspector</li>
        <li class="breadcrumb-item active">Agenda</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5>{{ $date->format('l, F j, Y') }}</h5>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('inspector.agenda', ['date' => $date->copy()->subDay()->format('Y-m-d')]) }}" class="btn btn-outline-primary btn-sm">
                                <i data-feather="chevron-left"></i> Previous
                            </a>
                            <a href="{{ route('inspector.agenda', ['date' => now()->format('Y-m-d')]) }}" class="btn btn-primary btn-sm">Today</a>
                            <a href="{{ route('inspector.agenda', ['date' => $date->copy()->addDay()->format('Y-m-d')]) }}" class="btn btn-outline-primary btn-sm">
                                Next <i data-feather="chevron-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @forelse($appointments as $appointment)
                            <div class="card mb-3 border {{ $appointment->status === 'in_progress' ? 'border-warning' : '' }}">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            <div class="text-center">
                                                <h4 class="mb-0 text-primary">{{ \Carbon\Carbon::parse($appointment->start)->format('g:i') }}</h4>
                                                <small class="text-muted">{{ \Carbon\Carbon::parse($appointment->start)->format('A') }}</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <h6 class="mb-1">{{ $appointment->userCase->user->name ?? 'N/A' }}</h6>
                                            <p class="mb-0 text-muted">
                                                {{ $appointment->userCase->user->phone ?? '' }}
                                            </p>
                                            @if($appointment->location)
                                                <small class="text-muted">
                                                    <i data-feather="map-pin" style="width: 12px;"></i> {{ $appointment->location }}
                                                </small>
                                            @endif
                                        </div>
                                        <div class="col-md-3">
                                            @if($appointment->userCase->vehicle)
                                                <p class="mb-0">
                                                    <strong>{{ $appointment->userCase->vehicle->year }} {{ $appointment->userCase->vehicle->make }}</strong>
                                                </p>
                                                <small class="text-muted">{{ $appointment->userCase->vehicle->model }}</small>
                                                <br>
                                                <small class="font-monospace">VIN: {{ substr($appointment->userCase->vehicle->vin ?? '', -6) }}</small>
                                            @else
                                                <span class="text-muted">Vehicle info pending</span>
                                            @endif
                                        </div>
                                        <div class="col-md-1 text-center">
                                            <span class="badge badge-{{
                                                $appointment->status === 'confirmed' ? 'success' :
                                                ($appointment->status === 'in_progress' ? 'warning' :
                                                ($appointment->status === 'completed' ? 'secondary' : 'primary'))
                                            }}">
                                                {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                                            </span>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <a href="{{ route('inspector.appointment.show', $appointment) }}" class="btn btn-outline-primary btn-sm">
                                                <i data-feather="eye"></i> View
                                            </a>
                                            @if($appointment->status !== 'completed')
                                                <a href="{{ route('inspector.checklist', $appointment) }}" class="btn btn-primary btn-sm">
                                                    <i data-feather="clipboard"></i> Inspect
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5">
                                <i data-feather="calendar" style="width: 64px; height: 64px; color: #ddd;"></i>
                                <h5 class="mt-3 text-muted">No appointments for this date</h5>
                                <p class="text-muted">Select another date or check your upcoming appointments.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
