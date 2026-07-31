@extends('layouts.admin.master')

@section('title')Verify Components @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Verify Components</h3>
        @endslot
        <li class="breadcrumb-item">Inspector</li>
        <li class="breadcrumb-item active">Components</li>
    @endcomponent

    <div class="container-fluid">
        <!-- Progress -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Verification Progress</span>
                            <span>{{ $verified_count }} / {{ $total_count }} components verified</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            @php $percent = $total_count > 0 ? round(($verified_count / $total_count) * 100) : 0; @endphp
                            <div class="progress-bar {{ $all_verified ? 'bg-success' : 'bg-warning' }}"
                                 role="progressbar"
                                 style="width: {{ $percent }}%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Components List -->
        <div class="row">
            @forelse($components as $component)
                <div class="col-md-6 mb-4">
                    <div class="card {{ $component->verified ? 'border-success' : '' }}">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">{{ ucfirst(str_replace('_', ' ', $component->part_type)) }}</h6>
                            @if($component->verified)
                                <span class="badge badge-success">Verified</span>
                            @else
                                <span class="badge badge-warning">Pending</span>
                            @endif
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Condition</small>
                                    <p class="mb-2">
                                        <span class="badge badge-{{ $component->condition === 'new' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($component->condition) }}
                                        </span>
                                    </p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Source</small>
                                    <p class="mb-2">{{ $component->serial_number ?? $component->receipt_number ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Part Number</small>
                                    <p class="mb-2 font-monospace">{{ $component->part_number ?? 'N/A' }}</p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Serial Number</small>
                                    <p class="mb-2 font-monospace">{{ $component->serial_number ?? 'N/A' }}</p>
                                </div>
                            </div>
                            @if($component->source_phone || $component->source_address)
                                <hr>
                                <small class="text-muted">Source Details</small>
                                <p class="mb-0">
                                    {{ $component->source_phone ?? '' }}
                                    {{ $component->source_phone && $component->source_address ? ' - ' : '' }}
                                    {{ $component->source_address ?? '' }}
                                </p>
                            @endif
                        </div>
                        <div class="card-footer">
                            @if(!$component->verified)
                                <form action="{{ route('api.inspections.verify-component', $inspection) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="component_id" value="{{ $component->id }}">
                                    <button type="submit" class="btn btn-success">
                                        <i data-feather="check"></i> Verify Component
                                    </button>
                                </form>
                            @else
                                <span class="text-success">
                                    <i data-feather="check-circle"></i>
                                    Verified on {{ $component->verified_at ? $component->verified_at->format('M j, Y g:i A') : 'N/A' }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i data-feather="box" style="width: 64px; height: 64px; color: #ddd;"></i>
                            <h5 class="mt-3 text-muted">No Components Registered</h5>
                            <p class="text-muted">No major components have been registered for this vehicle.</p>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Navigation -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body d-flex justify-content-between">
                        <a href="{{ route('inspector.photos', $inspection) }}" class="btn btn-outline-secondary">
                            <i data-feather="arrow-left"></i> Back to Photos
                        </a>
                        <a href="{{ route('inspector.summary', $inspection) }}" class="btn btn-primary">
                            Next: Review Summary <i data-feather="arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
