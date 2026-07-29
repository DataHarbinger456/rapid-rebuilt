@extends('layouts.admin.master')

@section('title')Inspection Checklist @endsection

@push('css')
<style>
    .checklist-item {
        padding: 1rem;
        border-bottom: 1px solid #eee;
        transition: background-color 0.2s;
    }
    .checklist-item:hover {
        background-color: #f8f9fa;
    }
    .checklist-item.checked {
        background-color: #d4edda;
    }
    .checklist-item .form-check-input {
        width: 1.5rem;
        height: 1.5rem;
    }
</style>
@endpush

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Inspection Checklist</h3>
        @endslot
        <li class="breadcrumb-item">Inspector</li>
        <li class="breadcrumb-item active">Checklist</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row">
            <!-- Vehicle Summary Card -->
            <div class="col-xl-4">
                <div class="card sticky-top" style="top: 80px;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Vehicle Info</h5>
                    </div>
                    <div class="card-body">
                        @if($vehicle)
                            <h6 class="font-monospace">{{ $vehicle->vin }}</h6>
                            <p class="mb-1"><strong>{{ $vehicle->year }} {{ $vehicle->make }} {{ $vehicle->model }}</strong></p>
                            <p class="mb-1 text-muted">{{ $vehicle->body_type ?? '' }} - {{ $vehicle->color ?? '' }}</p>
                            <p class="mb-0">Odometer: {{ number_format($vehicle->odometer_reading ?? 0) }} mi</p>
                        @else
                            <p class="text-muted">Vehicle info not available</p>
                        @endif
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Case #:</span>
                            <strong>{{ $case->case_number }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Customer:</span>
                            <strong>{{ $case->user->name ?? 'N/A' }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Inspection Status:</span>
                            <span class="badge badge-{{ $inspection->result === 'pending' ? 'warning' : ($inspection->result === 'passed' ? 'success' : 'danger') }}">
                                {{ ucfirst($inspection->result) }}
                            </span>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="progress mb-2" style="height: 20px;">
                            @php
                                $total = count($checklist_template);
                                $checked = collect($inspection->checklist_data ?? [])->filter()->count();
                                $percent = $total > 0 ? round(($checked / $total) * 100) : 0;
                            @endphp
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percent }}%">
                                {{ $percent }}%
                            </div>
                        </div>
                        <small class="text-muted">{{ $checked }} of {{ $total }} items checked</small>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5>Quick Actions</h5>
                    </div>
                    <div class="card-body d-grid gap-2">
                        <a href="{{ route('inspector.photos', $inspection) }}" class="btn btn-outline-primary">
                            <i data-feather="camera"></i> Photos ({{ $inspection->photos->count() }}/{{ count($required_photos) }})
                        </a>
                        <a href="{{ route('inspector.components', $inspection) }}" class="btn btn-outline-primary">
                            <i data-feather="box"></i> Verify Components
                        </a>
                        <a href="{{ route('inspector.summary', $inspection) }}" class="btn btn-outline-secondary">
                            <i data-feather="file-text"></i> View Summary
                        </a>
                    </div>
                </div>
            </div>

            <!-- Checklist -->
            <div class="col-xl-8">
                <form id="checklist-form" action="{{ route('api.inspections.update', $inspection) }}" method="POST">
                    @csrf
                    @method('PUT')

                    @foreach($checklist_template as $category => $items)
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5>{{ $category }}</h5>
                            </div>
                            <div class="card-body p-0">
                                @foreach($items as $key => $label)
                                    @php $isChecked = ($inspection->checklist_data[$key] ?? false); @endphp
                                    <div class="checklist-item {{ $isChecked ? 'checked' : '' }}">
                                        <div class="form-check d-flex align-items-center">
                                            <input class="form-check-input me-3 checklist-checkbox"
                                                   type="checkbox"
                                                   name="checklist_data[{{ $key }}]"
                                                   id="check_{{ $key }}"
                                                   value="1"
                                                   {{ $isChecked ? 'checked' : '' }}>
                                            <label class="form-check-label flex-grow-1" for="check_{{ $key }}">
                                                {{ $label }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <!-- Notes -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5>Inspector Notes</h5>
                        </div>
                        <div class="card-body">
                            <textarea name="notes" class="form-control" rows="4" placeholder="Add any notes about the inspection...">{{ $inspection->notes }}</textarea>
                        </div>
                    </div>

                    <!-- Deficiencies -->
                    <div class="card mb-3">
                        <div class="card-header bg-warning">
                            <h5 class="mb-0">Deficiencies Found</h5>
                        </div>
                        <div class="card-body">
                            <textarea name="deficiencies" class="form-control" rows="4" placeholder="List any deficiencies or issues found...">{{ $inspection->deficiencies }}</textarea>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i data-feather="save"></i> Save Progress
                                </button>

                                @if($can_pass)
                                    <a href="{{ route('inspector.summary', $inspection) }}" class="btn btn-success btn-lg">
                                        <i data-feather="check-circle"></i> Complete Inspection
                                    </a>
                                @else
                                    <div class="text-end">
                                        <span class="text-muted">Complete all required items to finish</span>
                                        @if(count($missing_photos) > 0)
                                            <br><small class="text-danger">Missing {{ count($missing_photos) }} required photos</small>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-save on checkbox change
    const checkboxes = document.querySelectorAll('.checklist-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const item = this.closest('.checklist-item');
            if (this.checked) {
                item.classList.add('checked');
            } else {
                item.classList.remove('checked');
            }
            // Could add auto-save AJAX here
        });
    });
});
</script>
@endpush
