@extends('layouts.admin.master')

@section('title')Inspection Summary @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Inspection Summary</h3>
        @endslot
        <li class="breadcrumb-item">Inspector</li>
        <li class="breadcrumb-item active">Summary</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row">
            <!-- Summary -->
            <div class="col-xl-8">
                <!-- Vehicle Info -->
                <div class="card">
                    <div class="card-header">
                        <h5>Vehicle Information</h5>
                    </div>
                    <div class="card-body">
                        @if($vehicle)
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="text-muted" width="40%">VIN:</td>
                                            <td><strong class="font-monospace">{{ $vehicle->vin }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Year/Make/Model:</td>
                                            <td>{{ $vehicle->year }} {{ $vehicle->make }} {{ $vehicle->model }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Body Type:</td>
                                            <td>{{ $vehicle->body_type ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Color:</td>
                                            <td>{{ $vehicle->color ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="text-muted" width="40%">Odometer:</td>
                                            <td>{{ number_format($vehicle->odometer_reading ?? 0) }} miles</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Title Number:</td>
                                            <td>{{ $vehicle->title_number ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Title State:</td>
                                            <td>{{ $vehicle->title_state ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Title Brand:</td>
                                            <td>{{ ucfirst($vehicle->title_brand ?? 'N/A') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        @else
                            <p class="text-muted">Vehicle information not available</p>
                        @endif
                    </div>
                </div>

                <!-- Components Summary -->
                <div class="card">
                    <div class="card-header">
                        <h5>Components Verified</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Part Type</th>
                                        <th>Condition</th>
                                        <th>Source</th>
                                        <th>Part #</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($components as $component)
                                        <tr>
                                            <td>{{ ucfirst(str_replace('_', ' ', $component->part_type)) }}</td>
                                            <td>{{ ucfirst($component->condition) }}</td>
                                            <td>{{ $component->source ?? 'N/A' }}</td>
                                            <td><code>{{ $component->part_number ?? '-' }}</code></td>
                                            <td>
                                                @if($component->verified)
                                                    <span class="text-success"><i data-feather="check-circle"></i></span>
                                                @else
                                                    <span class="text-danger"><i data-feather="x-circle"></i></span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No components</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Photos Summary -->
                <div class="card">
                    <div class="card-header">
                        <h5>Photos Taken</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @forelse($photos as $photo)
                                <div class="col-md-3 mb-3">
                                    <img src="{{ asset('storage/' . $photo->file_path) }}"
                                         alt="{{ $photo->photo_type }}"
                                         class="img-fluid rounded"
                                         style="height: 120px; width: 100%; object-fit: cover;">
                                    <small class="d-block text-center mt-1">{{ ucfirst(str_replace('_', ' ', $photo->photo_type)) }}</small>
                                </div>
                            @empty
                                <div class="col-12 text-center text-muted">No photos taken</div>
                            @endforelse
                        </div>
                        @if(count($missing_photos) > 0)
                            <div class="alert alert-warning mt-3">
                                <strong>Missing Photos:</strong> {{ implode(', ', array_map(fn($p) => ucfirst(str_replace('_', ' ', $p)), $missing_photos)) }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Inspector Notes -->
                @if($inspection->notes || $inspection->deficiencies)
                    <div class="card">
                        <div class="card-header">
                            <h5>Notes & Deficiencies</h5>
                        </div>
                        <div class="card-body">
                            @if($inspection->notes)
                                <div class="mb-3">
                                    <label class="text-muted">Inspector Notes</label>
                                    <p>{{ $inspection->notes }}</p>
                                </div>
                            @endif
                            @if($inspection->deficiencies)
                                <div class="alert alert-danger">
                                    <label class="text-danger"><strong>Deficiencies Found</strong></label>
                                    <p class="mb-0">{{ $inspection->deficiencies }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Actions Sidebar -->
            <div class="col-xl-4">
                <div class="card sticky-top" style="top: 80px;">
                    <div class="card-header {{ $inspection->result === 'passed' ? 'bg-success' : ($inspection->result === 'failed' ? 'bg-danger' : 'bg-warning') }} text-white">
                        <h5 class="mb-0">Inspection Status</h5>
                    </div>
                    <div class="card-body text-center">
                        @if($inspection->result === 'pending')
                            <i data-feather="clock" style="width: 64px; height: 64px; color: #ffc107;"></i>
                            <h4 class="mt-3">In Progress</h4>
                        @elseif($inspection->result === 'passed')
                            <i data-feather="check-circle" style="width: 64px; height: 64px; color: #28a745;"></i>
                            <h4 class="mt-3 text-success">PASSED</h4>
                        @else
                            <i data-feather="x-circle" style="width: 64px; height: 64px; color: #dc3545;"></i>
                            <h4 class="mt-3 text-danger">FAILED</h4>
                        @endif

                        <hr>

                        <div class="text-start">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Case #:</span>
                                <strong>{{ $case->case_number }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Customer:</span>
                                <strong>{{ $case->user->name ?? 'N/A' }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Started:</span>
                                <strong>{{ $inspection->started_at ? $inspection->started_at->format('M j, g:i A') : 'N/A' }}</strong>
                            </div>
                            @if($inspection->completed_at)
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Completed:</span>
                                    <strong>{{ $inspection->completed_at->format('M j, g:i A') }}</strong>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($inspection->result === 'pending')
                        <div class="card-footer">
                            @if($can_pass)
                                <form action="{{ route('api.inspections.complete', $inspection) }}" method="POST" class="mb-2">
                                    @csrf
                                    <input type="hidden" name="result" value="passed">
                                    <button type="submit" class="btn btn-success btn-lg w-100">
                                        <i data-feather="check-circle"></i> Mark as PASSED
                                    </button>
                                </form>
                                <form action="{{ route('api.inspections.complete', $inspection) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="result" value="failed">
                                    <button type="submit" class="btn btn-outline-danger w-100">
                                        <i data-feather="x-circle"></i> Mark as FAILED
                                    </button>
                                </form>
                            @else
                                <div class="alert alert-warning mb-0">
                                    <strong>Cannot complete inspection:</strong>
                                    <ul class="mb-0 mt-2">
                                        @if(count($missing_photos) > 0)
                                            <li>Missing {{ count($missing_photos) }} required photos</li>
                                        @endif
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @elseif($inspection->result === 'passed')
                        <div class="card-footer">
                            <a href="{{ route('inspector.documents', $inspection) }}" class="btn btn-primary btn-lg w-100">
                                <i data-feather="file-text"></i> Generate Documents
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection
