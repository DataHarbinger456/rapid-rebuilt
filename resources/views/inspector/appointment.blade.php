@extends('layouts.admin.master')

@section('title')Appointment Details @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Appointment Details</h3>
        @endslot
        <li class="breadcrumb-item">Inspector</li>
        <li class="breadcrumb-item"><a href="{{ route('inspector.agenda') }}">Agenda</a></li>
        <li class="breadcrumb-item active">Appointment</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row">
            <!-- Appointment Info -->
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Appointment Info</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="text-muted">Date & Time</label>
                            <h6>{{ \Carbon\Carbon::parse($appointment->start)->format('l, F j, Y') }}</h6>
                            <h4 class="text-primary">{{ \Carbon\Carbon::parse($appointment->start)->format('g:i A') }}</h4>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted">Status</label>
                            <div>
                                <span class="badge badge-{{
                                    $appointment->status === 'confirmed' ? 'success' :
                                    ($appointment->status === 'in_progress' ? 'warning' :
                                    ($appointment->status === 'completed' ? 'secondary' : 'primary'))
                                }} p-2">
                                    {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                                </span>
                            </div>
                        </div>
                        @if($appointment->location)
                            <div class="mb-3">
                                <label class="text-muted">Location</label>
                                <p class="mb-0">{{ $appointment->location }}</p>
                            </div>
                        @endif
                        @if($appointment->notes)
                            <div class="mb-3">
                                <label class="text-muted">Notes</label>
                                <p class="mb-0">{{ $appointment->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Customer Info -->
                <div class="card">
                    <div class="card-header">
                        <h5>Customer</h5>
                    </div>
                    <div class="card-body">
                        @if($appointment->userCase->user)
                            <h6>{{ $appointment->userCase->user->name }}</h6>
                            <p class="mb-1">
                                <i data-feather="mail" style="width: 14px;"></i>
                                {{ $appointment->userCase->user->email }}
                            </p>
                            <p class="mb-1">
                                <i data-feather="phone" style="width: 14px;"></i>
                                {{ $appointment->userCase->user->phone ?? 'N/A' }}
                            </p>
                        @else
                            <p class="text-muted">Customer info not available</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Vehicle & Components -->
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Vehicle Information</h5>
                    </div>
                    <div class="card-body">
                        @if($appointment->userCase->vehicle)
                            @php $vehicle = $appointment->userCase->vehicle; @endphp
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="text-muted">VIN:</td>
                                            <td><strong class="font-monospace">{{ $vehicle->vin }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Year:</td>
                                            <td>{{ $vehicle->year }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Make:</td>
                                            <td>{{ $vehicle->make }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Model:</td>
                                            <td>{{ $vehicle->model }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="text-muted">Body Type:</td>
                                            <td>{{ $vehicle->body_type ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Color:</td>
                                            <td>{{ $vehicle->color ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Odometer:</td>
                                            <td>{{ number_format($vehicle->odometer_reading ?? 0) }} mi</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Title Brand:</td>
                                            <td>{{ ucfirst($vehicle->title_brand ?? 'N/A') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        @else
                            <p class="text-muted text-center py-3">Vehicle information not available</p>
                        @endif
                    </div>
                </div>

                <!-- Components -->
                <div class="card">
                    <div class="card-header">
                        <h5>Major Components</h5>
                        <span>{{ $appointment->userCase->components->count() }} components to verify</span>
                    </div>
                    <div class="card-body">
                        @if($appointment->userCase->components->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Part Type</th>
                                            <th>Condition</th>
                                            <th>Source</th>
                                            <th>Part #</th>
                                            <th>Verified</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($appointment->userCase->components as $component)
                                            <tr>
                                                <td>{{ ucfirst(str_replace('_', ' ', $component->part_type)) }}</td>
                                                <td>
                                                    <span class="badge badge-{{ $component->condition === 'new' ? 'success' : 'secondary' }}">
                                                        {{ ucfirst($component->condition) }}
                                                    </span>
                                                </td>
                                                <td>{{ $component->serial_number ?? $component->receipt_number ?? 'N/A' }}</td>
                                                <td><code>{{ $component->part_number ?? '-' }}</code></td>
                                                <td>
                                                    @if($component->verified)
                                                        <i data-feather="check-circle" class="text-success"></i>
                                                    @else
                                                        <i data-feather="circle" class="text-muted"></i>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center py-3">No components registered</p>
                        @endif
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div class="card-body text-center">
                        @if($appointment->status !== 'completed')
                            <a href="{{ route('inspector.checklist', $appointment) }}" class="btn btn-primary btn-lg">
                                <i data-feather="clipboard"></i> Start Inspection
                            </a>
                        @else
                            @if($appointment->userCase->inspection)
                                <a href="{{ route('inspector.summary', $appointment->userCase->inspection) }}" class="btn btn-outline-primary btn-lg">
                                    <i data-feather="file-text"></i> View Inspection Summary
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
