@extends('layouts.customer.master')

@section('title')Case #{{ $case->case_number }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Case #{{ $case->case_number }}</h3>
        @endslot
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customer.cases.index') }}">Cases</a></li>
        <li class="breadcrumb-item active">{{ $case->case_number }}</li>
    @endcomponent

    <div class="container-fluid">
        <!-- Status Banner -->
        @if($case->hasRequiredAction)
            <div class="alert alert-warning d-flex justify-content-between align-items-center mb-4">
                <div>
                    <i data-feather="alert-circle"></i>
                    <strong>{{ $case->requiredAction['title'] ?? 'Action Required' }}</strong>
                    <span class="ms-2">{{ $case->requiredAction['description'] ?? '' }}</span>
                </div>
                <a href="{{ $case->requiredAction['url'] ?? '#' }}" class="btn btn-warning">
                    {{ $case->requiredAction['button'] ?? 'Take Action' }}
                </a>
            </div>
        @endif

        <div class="row">
            <!-- Main Content -->
            <div class="col-xl-8">
                <!-- Progress Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Case Progress</h5>
                            <span class="badge badge-{{ $case->status_color }} badge-lg">{{ $case->status_label }}</span>
                        </div>
                        <div class="progress mb-3" style="height: 25px;">
                            <div class="progress-bar progress-bar-striped bg-primary" role="progressbar"
                                 style="width: {{ $case->progress_percent }}%;"
                                 aria-valuenow="{{ $case->progress_percent }}" aria-valuemin="0" aria-valuemax="100">
                                {{ $case->progress_percent }}%
                            </div>
                        </div>
                        <div class="d-flex justify-content-between text-muted small">
                            <span>Submitted</span>
                            <span>Documents</span>
                            <span>Scheduled</span>
                            <span>Inspection</span>
                            <span>Complete</span>
                        </div>
                    </div>
                </div>

                <!-- Vehicle Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i data-feather="truck"></i> Vehicle Information</h5>
                    </div>
                    <div class="card-body">
                        @if($case->vehicle)
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="text-muted">VIN:</td>
                                            <td><strong>{{ $case->vehicle->vin }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Year:</td>
                                            <td>{{ $case->vehicle->year }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Make:</td>
                                            <td>{{ $case->vehicle->make }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Model:</td>
                                            <td>{{ $case->vehicle->model }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="text-muted">Color:</td>
                                            <td>{{ ucfirst($case->vehicle->color ?? '-') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Body Style:</td>
                                            <td>{{ $case->vehicle->body_type ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Odometer:</td>
                                            <td>{{ number_format($case->vehicle->odometer_reading ?? 0) }} miles</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Title State:</td>
                                            <td>{{ $case->vehicle->title_state ?? '-' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        @else
                            <p class="text-muted">Vehicle information not yet added.</p>
                        @endif
                    </div>
                </div>

                <!-- Components -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i data-feather="settings"></i> Major Components</h5>
                        <span class="badge badge-primary">{{ $case->components->count() }} components</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Component</th>
                                    <th>Source</th>
                                    <th>Condition</th>
                                    <th>Verified</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($case->components as $component)
                                    <tr>
                                        <td>
                                            <strong>{{ $component->part_type_label }}</strong>
                                            @if($component->other_description)
                                                <br><small class="text-muted">{{ $component->other_description }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $component->serial_number ?? $component->receipt_number ?? '—' }}</td>
                                        <td>
                                            <span class="badge badge-secondary">
                                                {{ $component->condition_label }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($component->verified)
                                                <i data-feather="check-circle" class="text-success"></i>
                                            @else
                                                <i data-feather="clock" class="text-muted"></i>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No components listed</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Documents -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i data-feather="file-text"></i> Documents</h5>
                        @if(in_array($case->status, ['draft', 'pending_documents']))
                            <a href="{{ route('customer.cases.documents', $case) }}" class="btn btn-sm btn-primary">
                                <i data-feather="upload"></i> Upload Documents
                            </a>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Document</th>
                                    <th>Status</th>
                                    <th>Uploaded</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($case->documents as $document)
                                    <tr>
                                        <td>
                                            <i data-feather="{{ $document->icon ?? 'file' }}" class="text-{{ $document->status_color }}"></i>
                                            {{ $document->document_type_label }}
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $document->status_color }}">
                                                {{ ucfirst($document->status) }}
                                            </span>
                                            @if($document->rejection_reason)
                                                <br><small class="text-danger">{{ $document->rejection_reason }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $document->created_at->format('M j, Y') }}</td>
                                        <td>
                                            <a href="{{ route('api.documents.download', $document) }}" class="btn btn-sm btn-outline-primary">
                                                <i data-feather="download"></i>
                                            </a>
                                            @if($document->status === 'rejected')
                                                <a href="{{ route('customer.documents.reupload', $document) }}" class="btn btn-sm btn-outline-warning">
                                                    <i data-feather="upload"></i> Re-upload
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            No documents uploaded yet.
                                            @if(in_array($case->status, ['draft', 'pending_documents']))
                                                <br><a href="{{ route('customer.cases.documents', $case) }}">Upload required documents</a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="card">
                    <div class="card-header">
                        <h5><i data-feather="clock"></i> Case Timeline</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline-wrapper">
                            @forelse($case->timeline->sortByDesc('created_at') as $event)
                                <div class="timeline-item mb-4">
                                    <div class="d-flex">
                                        <div class="timeline-icon bg-{{ $event->type_color ?? 'primary' }} text-white rounded-circle p-2 me-3">
                                            <i data-feather="{{ $event->icon ?? 'circle' }}"></i>
                                        </div>
                                        <div class="timeline-content flex-grow-1">
                                            <div class="d-flex justify-content-between">
                                                <h6 class="mb-1">{{ $event->title }}</h6>
                                                <small class="text-muted">{{ $event->created_at->format('M j, Y g:i A') }}</small>
                                            </div>
                                            <p class="text-muted mb-0">{{ $event->description }}</p>
                                            @if($event->metadata && isset($event->metadata['by']))
                                                <small class="text-muted">By: {{ $event->metadata['by'] }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted text-center">No activity yet</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-xl-4">
                <!-- Quick Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Case Information</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Case Number:</span>
                                <strong>{{ $case->case_number }}</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Status:</span>
                                <span class="badge badge-{{ $case->status_color }}">{{ $case->status_label }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Created:</span>
                                <span>{{ $case->created_at->format('M j, Y') }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Last Updated:</span>
                                <span>{{ $case->updated_at->diffForHumans() }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Appointment -->
                @if($case->appointment)
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i data-feather="calendar"></i> Scheduled Inspection</h5>
                        </div>
                        <div class="card-body text-center">
                            <h4>{{ $case->appointment->start->format('l') }}</h4>
                            <h5>{{ $case->appointment->start->format('F j, Y') }}</h5>
                            <h3 class="text-primary">{{ $case->appointment->start->format('g:i A') }}</h3>
                            @if($case->appointment->location)
                                <p class="text-muted mt-3">
                                    <i data-feather="map-pin"></i> {{ $case->appointment->location }}
                                </p>
                            @endif
                            @if($case->appointment->inspector)
                                <p class="text-muted">
                                    <i data-feather="user"></i> Inspector: {{ $case->appointment->inspector->name }}
                                </p>
                            @endif
                            @if($case->appointment->canReschedule())
                                <a href="{{ route('customer.appointments.reschedule', $case->appointment) }}" class="btn btn-outline-primary btn-sm">
                                    <i data-feather="clock"></i> Reschedule
                                </a>
                            @endif
                        </div>
                    </div>
                @elseif(in_array($case->status, ['paid', 'documents_complete', 'pending_scheduling']))
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Schedule Inspection</h5>
                        </div>
                        <div class="card-body text-center">
                            <i data-feather="calendar" style="width: 48px; height: 48px;" class="text-primary mb-3"></i>
                            <p class="text-muted">Your case is ready for scheduling.</p>
                            <a href="{{ route('customer.cases.schedule', $case) }}" class="btn btn-primary">
                                <i data-feather="calendar"></i> Schedule Now
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Payment Status -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Payment</h5>
                    </div>
                    <div class="card-body">
                        @if($case->payment_status === 'paid')
                            <div class="text-center text-success">
                                <i data-feather="check-circle" style="width: 48px; height: 48px;"></i>
                                <h5 class="mt-2">Paid</h5>
                                <p class="text-muted">US${{ number_format($case->total_amount, 2, '.', ',') }}</p>
                            </div>
                        @elseif($case->status === 'pending_payment')
                            <div class="text-center">
                                <h4 class="text-primary">US${{ number_format($case->total_amount, 2, '.', ',') }}</h4>
                                <p class="text-muted">Payment required to proceed</p>
                                <a href="{{ route('customer.cases.pay', $case) }}" class="btn btn-success btn-lg w-100">
                                    <i data-feather="credit-card"></i> Pay Now
                                </a>
                            </div>
                        @else
                            <p class="text-muted text-center mb-0">
                                Estimated: US${{ number_format($case->total_amount ?? 0, 2, '.', ',') }}
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Generated Documents -->
                @if($case->generatedDocuments && $case->generatedDocuments->count() > 0)
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i data-feather="file-text"></i> HSMV Documents</h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                @foreach($case->generatedDocuments as $doc)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>{{ $doc->name }}</span>
                                        <a href="{{ route('api.documents.download', $doc) }}" class="btn btn-sm btn-outline-primary">
                                            <i data-feather="download"></i>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <!-- Actions -->
                <div class="card">
                    <div class="card-header">
                        <h5>Actions</h5>
                    </div>
                    <div class="card-body">
                        @if($case->status === 'draft')
                            <a href="{{ route('customer.cases.edit', $case) }}" class="btn btn-primary w-100 mb-2">
                                <i data-feather="edit"></i> Continue Editing
                            </a>
                            <form action="{{ route('customer.cases.destroy', $case) }}" method="POST"
                                  onsubmit="return confirm('Are you sure you want to delete this draft?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i data-feather="trash-2"></i> Delete Draft
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('customer.messages.create', ['case_id' => $case->id]) }}" class="btn btn-outline-primary w-100 mt-2">
                            <i data-feather="message-circle"></i> Contact Support
                        </a>

                        <button type="button" class="btn btn-outline-secondary w-100 mt-2" id="copy-tracking-link"
                                data-tracking-url="{{ $case->tracking_url }}">
                            <i data-feather="link"></i> Share Tracking Link
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
<style>
.timeline-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.timeline-icon svg {
    width: 18px;
    height: 18px;
}
</style>
@endpush

@push('scripts')
<script>
document.getElementById('copy-tracking-link')?.addEventListener('click', function () {
    var btn = this;
    navigator.clipboard.writeText(btn.dataset.trackingUrl).then(function () {
        var original = btn.innerHTML;
        btn.innerHTML = 'Link copied!';
        setTimeout(function () {
            btn.innerHTML = original;
            if (window.feather) feather.replace();
        }, 2000);
    });
});
</script>
@endpush
