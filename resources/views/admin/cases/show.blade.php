@extends('layouts.admin.master')

@section('title')Case Details @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Case #{{ $case->case_number }}</h3>
        @endslot
        <li class="breadcrumb-item">Admin</li>
        <li class="breadcrumb-item"><a href="{{ route('admin.cases.index') }}">Cases</a></li>
        <li class="breadcrumb-item active">{{ $case->case_number }}</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row">
            <!-- Main Content -->
            <div class="col-xl-8">
                <!-- Customer Info -->
                <div class="card">
                    <div class="card-header">
                        <h5>Customer Information</h5>
                    </div>
                    <div class="card-body">
                        @if($case->user)
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Name:</strong> {{ $case->user->name }}</p>
                                    <p><strong>Email:</strong> {{ $case->user->email }}</p>
                                    <p><strong>Phone:</strong> {{ $case->user->phone ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Address:</strong> {{ $case->user->address_1 ?? 'N/A' }}</p>
                                    <p><strong>City/State:</strong> {{ $case->user->city ?? '' }}, {{ $case->user->states ?? '' }} {{ $case->user->zip ?? '' }}</p>
                                </div>
                            </div>
                            @if($case->user->isDealer())
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p>
                                            <span class="badge badge-primary">Dealer</span>
                                            <strong class="ms-2">{{ $case->user->dealer_name }}</strong>
                                        </p>
                                        <p><strong>License:</strong> {{ $case->user->license_type }}-{{ $case->user->license_number }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>County:</strong> {{ $case->user->county ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            @endif
                        @else
                            <p class="text-muted">Customer information not available</p>
                        @endif
                    </div>
                </div>

                <!-- Vehicle Info -->
                <div class="card">
                    <div class="card-header">
                        <h5>Vehicle Information</h5>
                    </div>
                    <div class="card-body">
                        @if($case->vehicle)
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr><td class="text-muted">VIN:</td><td><strong class="font-monospace">{{ $case->vehicle->vin }}</strong></td></tr>
                                        <tr><td class="text-muted">Year:</td><td>{{ $case->vehicle->year }}</td></tr>
                                        <tr><td class="text-muted">Make:</td><td>{{ $case->vehicle->make }}</td></tr>
                                        <tr><td class="text-muted">Model:</td><td>{{ $case->vehicle->model }}</td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr><td class="text-muted">Body Type:</td><td>{{ $case->vehicle->body_type ?? 'N/A' }}</td></tr>
                                        <tr><td class="text-muted">Color:</td><td>{{ $case->vehicle->color ?? 'N/A' }}</td></tr>
                                        <tr><td class="text-muted">Odometer:</td><td>{{ number_format($case->vehicle->odometer_reading ?? 0) }} mi</td></tr>
                                        <tr><td class="text-muted">Title Brand:</td><td>{{ ucfirst($case->vehicle->title_brand ?? 'N/A') }}</td></tr>
                                    </table>
                                </div>
                            </div>
                        @else
                            <p class="text-muted">Vehicle information not available</p>
                        @endif
                    </div>
                </div>

                <!-- Document Readiness -->
                @include('partials.document-readiness', ['readiness' => $readiness, 'variant' => 'admin'])

                <!-- Documents -->
                <div class="card">
                    <div class="card-header">
                        {{-- State lives in the readiness meter directly above. --}}
                        <h5>Documents</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Document Type</th>
                                        <th>Status</th>
                                        <th>Uploaded</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($case->documents as $doc)
                                        <tr>
                                            <td>{{ $doc->document_type_label }}</td>
                                            <td>
                                                <span class="badge badge-{{
                                                    $doc->status === 'approved' ? 'success' :
                                                    ($doc->status === 'rejected' ? 'danger' : 'warning')
                                                }}">
                                                    {{ ucfirst($doc->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $doc->created_at->format('M j, Y') }}</td>
                                            <td>
                                                <a href="{{ route('api.documents.view', $doc) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">View</a>
                                                @php($extraction = $doc->currentExtraction())
                                                @if($extraction)
                                                    <a href="{{ route('admin.extractions.show', [$case->id, $extraction->id]) }}"
                                                       class="btn btn-sm btn-outline-info">{{ $extraction->reviewed_at ? 'Extraction reviewed' : 'Review extraction' }}</a>
                                                @elseif(config("extraction.documents.{$doc->document_type}.extractor"))
                                                    <form method="POST" class="d-inline"
                                                          action="{{ route('admin.extractions.store', [$case->id, $doc->id]) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-info">Read with AI</button>
                                                    </form>
                                                @endif
                                                @if($doc->status === 'pending')
                                                    <button type="button" class="btn btn-sm btn-success js-approve-document"
                                                            data-url="{{ route('api.documents.approve', [$case, $doc]) }}">Approve</button>
                                                    <button type="button" class="btn btn-sm btn-danger js-reject-document"
                                                            data-url="{{ route('api.documents.reject', [$case, $doc]) }}">Reject</button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No documents uploaded</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Components -->
                <div class="card">
                    <div class="card-header">
                        <h5>Major Components</h5>
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
                                        <th>Verified</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($case->components as $component)
                                        <tr>
                                            <td>{{ ucfirst(str_replace('_', ' ', $component->part_type)) }}</td>
                                            <td><span class="badge badge-secondary">{{ ucfirst($component->condition) }}</span></td>
                                            <td>{{ $component->serial_number ?? $component->receipt_number ?? 'N/A' }}</td>
                                            <td><code>{{ $component->part_number ?? '-' }}</code></td>
                                            <td>
                                                @if($component->verified)
                                                    <span class="text-success"><i data-feather="check-circle"></i></span>
                                                @else
                                                    <span class="text-muted"><i data-feather="circle"></i></span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No components registered</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="card">
                    <div class="card-header">
                        <h5>Case Timeline</h5>
                    </div>
                    <div class="card-body">
                        @forelse($case->timeline->sortByDesc('created_at') as $event)
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0 me-3">
                                    <div class="bg-light rounded-circle p-2">
                                        <i data-feather="{{ $event->event === 'status_changed' ? 'refresh-cw' : 'activity' }}" style="width: 16px; height: 16px;"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-1"><strong>{{ $event->title }}</strong></p>
                                    <p class="mb-1 text-muted">{{ $event->description }}</p>
                                    <small class="text-muted">{{ $event->created_at->format('M j, Y g:i A') }}</small>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted text-center">No timeline events</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-xl-4">
                <!-- Status Card -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Case Status</h5>
                    </div>
                    <div class="card-body text-center">
                        <span class="badge badge-lg p-3" style="font-size: 1rem;">
                            {{ ucfirst(str_replace('_', ' ', $case->status)) }}
                        </span>
                        <hr>
                        <form action="{{ route('admin.cases.updateStatus', $case) }}" method="POST" class="mb-3">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Change Status</label>
                                <select name="status" class="form-select">
                                    @foreach($available_statuses as $status)
                                        <option value="{{ $status }}" {{ $case->status === $status ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Update Status</button>
                        </form>
                    </div>
                </div>

                <!-- Assignment -->
                <div class="card">
                    <div class="card-header">
                        <h5>Inspector Assignment</h5>
                    </div>
                    <div class="card-body">
                        @if($case->assignedInspector)
                            <p><strong>Assigned to:</strong> {{ $case->assignedInspector->name }}</p>
                        @else
                            <p class="text-muted">No inspector assigned</p>
                        @endif
                        <form action="{{ route('admin.cases.assign', $case) }}" method="POST">
                            @csrf
                            <select name="inspector_id" class="form-select mb-2" required>
                                <option value="">Select Inspector...</option>
                                @foreach(\App\Models\User::inspectors()->get() as $inspector)
                                    <option value="{{ $inspector->id }}" {{ $case->assigned_inspector_id == $inspector->id ? 'selected' : '' }}>
                                        {{ $inspector->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-outline-primary w-100">Assign Inspector</button>
                        </form>
                    </div>
                </div>

                <!-- DMV Forms -->
                <div class="card">
                    <div class="card-header">
                        <h5>DMV Forms</h5>
                    </div>
                    <div class="card-body">
                        {{-- Inline single-statement form on purpose. Blade pairs the
                             first raw-PHP opener in a file with the first closer, and
                             this view already has an inline one further up, so a block
                             here would swallow everything in between. The label map
                             lives on the Inspection model. --}}
                        @php($existing82040 = $case->documents->firstWhere('document_type', 'hsmv_82040'))

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span>Inspection:</span>
                            @if($case->inspection)
                                <span class="badge bg-{{ $case->inspection->result_colour }}">
                                    {{ $case->inspection->result_label }}
                                </span>
                            @else
                                <span class="badge bg-secondary">No inspection yet</span>
                            @endif
                        </div>

                        <form action="{{ route('admin.cases.generate82040', $case) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100">
                                {{ $existing82040 ? 'Regenerate HSMV 82040' : 'Generate HSMV 82040' }}
                            </button>
                        </form>

                        @if($existing82040)
                            <a href="{{ route('api.documents.view', $existing82040) }}"
                               target="_blank" rel="noopener"
                               class="btn btn-sm btn-link w-100 mt-1">View current 82040</a>
                        @endif

                        {{-- The 82040 is filled from the vehicle record alone, so it does not
                             wait on an inspection. The 84490 Statement of Builder does, and
                             stays in the inspector flow. --}}
                        <p class="text-muted small mb-0 mt-2">
                            The 82040 uses the confirmed vehicle details and does not require an
                            inspection. Check the vehicle record above before sending it to the state.
                        </p>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="card">
                    <div class="card-header">
                        <h5>Payment Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Amount:</span>
                            <strong>US${{ number_format($case->total_amount ?? 0, 2, '.', ',') }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Status:</span>
                            <span class="badge badge-{{ $case->is_paid ? 'success' : 'warning' }}">
                                {{ $case->is_paid ? 'Paid' : 'Pending' }}
                            </span>
                        </div>
                        @if($case->paid_at)
                            <div class="d-flex justify-content-between">
                                <span>Paid on:</span>
                                <span>{{ $case->paid_at->format('M j, Y') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Inspection -->
                @if($case->inspection)
                    <div class="card">
                        <div class="card-header">
                            <h5>Inspection</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Result:</span>
                                <span class="badge badge-{{ $case->inspection->result === 'passed' ? 'success' : ($case->inspection->result === 'failed' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($case->inspection->result) }}
                                </span>
                            </div>
                            @if($case->inspection->completed_at)
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Completed:</span>
                                    <span>{{ $case->inspection->completed_at->format('M j, Y') }}</span>
                                </div>
                            @endif
                            <div class="d-flex justify-content-between">
                                <span>Photos:</span>
                                <span>{{ $case->inspection->photos->count() }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Add Note -->
                <div class="card">
                    <div class="card-header">
                        <h5>Add Note</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.cases.addNote', $case) }}" method="POST">
                            @csrf
                            <textarea name="note" class="form-control mb-2" rows="3" placeholder="Add an internal note..." required></textarea>
                            <button type="submit" class="btn btn-outline-primary w-100">Add Note</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
// Approve/reject post through a real form so CSRF and redirects behave normally.
function submitDocumentReview(url, fields) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = url;

    fields._token = document.querySelector('meta[name="csrf-token"]').content;
    Object.keys(fields).forEach(function (name) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = fields[name];
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}

document.querySelectorAll('.js-approve-document').forEach(function (button) {
    button.addEventListener('click', function () {
        submitDocumentReview(this.dataset.url, {});
    });
});

document.querySelectorAll('.js-reject-document').forEach(function (button) {
    button.addEventListener('click', function () {
        var reason = window.prompt('Why is this document being rejected? The customer will see this.');
        if (reason && reason.trim()) {
            submitDocumentReview(this.dataset.url, { reason: reason.trim() });
        }
    });
});
</script>
@endpush