@extends('layouts.admin.master')

@section('title')Case Management @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Case Management</h3>
        @endslot
        <li class="breadcrumb-item">Admin</li>
        <li class="breadcrumb-item active">Cases</li>
    @endcomponent

    <div class="container-fluid">
        <!-- Filters -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Filters</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('admin.cases.index') }}">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="">All Statuses</option>
                                            @foreach($statuses as $key => $label)
                                                <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Inspector</label>
                                        <select name="inspector_id" class="form-select">
                                            <option value="">All Inspectors</option>
                                            @foreach($inspectors as $inspector)
                                                <option value="{{ $inspector->id }}" {{ request('inspector_id') == $inspector->id ? 'selected' : '' }}>
                                                    {{ $inspector->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Payment Status</label>
                                        <select name="payment_status" class="form-select">
                                            <option value="">All</option>
                                            <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                                            <option value="refunded" {{ request('payment_status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Search</label>
                                        <input type="text" name="search" class="form-control" placeholder="Case #, customer, VIN..." value="{{ request('search') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">From Date</label>
                                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">To Date</label>
                                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                                    </div>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="mb-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i data-feather="search"></i> Search
                                        </button>
                                        <a href="{{ route('admin.cases.index') }}" class="btn btn-outline-secondary">
                                            <i data-feather="x"></i> Clear
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cases Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Cases ({{ $cases->total() }})</h5>
                        <div>
                            <a href="{{ route('admin.cases.index', array_merge(request()->query(), ['export' => 1])) }}" class="btn btn-outline-primary btn-sm">
                                <i data-feather="download"></i> Export
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="selectAll" class="form-check-input">
                                        </th>
                                        <th>Case #</th>
                                        <th>Customer</th>
                                        <th>Vehicle</th>
                                        <th>Status</th>
                                        <th>Inspector</th>
                                        <th>Payment</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($cases as $case)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input case-checkbox" value="{{ $case->id }}">
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.cases.show', $case) }}">
                                                    <strong>{{ $case->case_number }}</strong>
                                                </a>
                                            </td>
                                            <td>
                                                {{ $case->user->name ?? 'N/A' }}
                                                <br>
                                                <small class="text-muted">{{ $case->user->email ?? '' }}</small>
                                            </td>
                                            <td>
                                                @if($case->vehicle)
                                                    {{ $case->vehicle->year }} {{ $case->vehicle->make }}
                                                    <br>
                                                    <small class="text-muted font-monospace">{{ substr($case->vehicle->vin ?? '', -6) }}</small>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-{{
                                                    in_array($case->status, ['completed', 'title_issued']) ? 'success' :
                                                    (in_array($case->status, ['cancelled', 'failed']) ? 'danger' :
                                                    (in_array($case->status, ['pending_review', 'inspection_in_progress']) ? 'warning' : 'secondary'))
                                                }}">
                                                    {{ ucfirst(str_replace('_', ' ', $case->status)) }}
                                                </span>
                                            </td>
                                            <td>{{ $case->assignedInspector->name ?? '-' }}</td>
                                            <td>
                                                @if($case->is_paid)
                                                    <span class="text-success">US${{ number_format($case->total_amount, 2, '.', ',') }}</span>
                                                @else
                                                    <span class="text-muted">Pending</span>
                                                @endif
                                            </td>
                                            <td>{{ $case->created_at->format('M j, Y') }}</td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="{{ route('admin.cases.show', $case) }}" class="btn btn-outline-primary btn-sm" title="View">
                                                        <i data-feather="eye"></i>
                                                    </a>
                                                    @if(auth()->user()->isAdmin())
                                                        <button type="button" class="btn btn-outline-danger btn-sm delete-case-btn"
                                                                data-case-id="{{ $case->id }}"
                                                                data-case-number="{{ $case->case_number }}"
                                                                title="Delete">
                                                            <i data-feather="trash-2"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-4 text-muted">
                                                No cases found matching your criteria
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if($cases->hasPages())
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <div class="text-muted">
                                    Showing {{ $cases->firstItem() ?? 0 }} to {{ $cases->lastItem() ?? 0 }} of {{ $cases->total() }} cases
                                </div>
                                <nav>
                                    <ul class="pagination mb-0">
                                        {{-- Previous Page Link --}}
                                        @if($cases->onFirstPage())
                                            <li class="page-item disabled">
                                                <span class="page-link">Previous</span>
                                            </li>
                                        @else
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $cases->previousPageUrl() }}&{{ http_build_query(request()->except('page')) }}">Previous</a>
                                            </li>
                                        @endif

                                        {{-- Page Numbers --}}
                                        @foreach($cases->getUrlRange(1, $cases->lastPage()) as $page => $url)
                                            @if($page == $cases->currentPage())
                                                <li class="page-item active">
                                                    <span class="page-link">{{ $page }}</span>
                                                </li>
                                            @else
                                                <li class="page-item">
                                                    <a class="page-link" href="{{ $url }}&{{ http_build_query(request()->except('page')) }}">{{ $page }}</a>
                                                </li>
                                            @endif
                                        @endforeach

                                        {{-- Next Page Link --}}
                                        @if($cases->hasMorePages())
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $cases->nextPageUrl() }}&{{ http_build_query(request()->except('page')) }}">Next</a>
                                            </li>
                                        @else
                                            <li class="page-item disabled">
                                                <span class="page-link">Next</span>
                                            </li>
                                        @endif
                                    </ul>
                                </nav>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    @if(auth()->user()->isAdmin())
        <div class="modal fade" id="deleteCaseModal" tabindex="-1" aria-labelledby="deleteCaseModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteCaseModalLabel">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete case <strong id="deleteCaseNumber"></strong>?</p>
                        <p class="text-danger"><small>This action cannot be undone. All related documents, timeline, and inspection data will be permanently deleted.</small></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form id="deleteCaseForm" method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete Case</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

@endsection

@push('scripts')
<script>
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.case-checkbox').forEach(cb => cb.checked = this.checked);
});

// Delete case functionality
document.querySelectorAll('.delete-case-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var caseId = this.getAttribute('data-case-id');
        var caseNumber = this.getAttribute('data-case-number');

        document.getElementById('deleteCaseNumber').textContent = caseNumber;
        document.getElementById('deleteCaseForm').action = '{{ url("admin/cases") }}/' + caseId;

        var modal = new bootstrap.Modal(document.getElementById('deleteCaseModal'));
        modal.show();
    });
});
</script>
@endpush
