@extends('layouts.customer.master')

@section('title')My Documents @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>My Documents</h3>
        @endslot
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Documents</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <!-- Pending Actions -->
                @if(isset($pendingDocuments) && $pendingDocuments->count() > 0)
                    <div class="card mb-4 border-warning">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0"><i data-feather="alert-circle"></i> Documents Requiring Attention</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Case</th>
                                        <th>Document</th>
                                        <th>Status</th>
                                        <th>Reason</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingDocuments as $doc)
                                        <tr>
                                            <td>
                                                <a href="{{ route('customer.cases.show', $doc->case) }}">
                                                    #{{ $doc->case->case_number }}
                                                </a>
                                            </td>
                                            <td>{{ $doc->document_type_label }}</td>
                                            <td><span class="badge badge-{{ $doc->status_color }}">{{ ucfirst($doc->status) }}</span></td>
                                            <td>{{ $doc->rejection_reason ?? '-' }}</td>
                                            <td>
                                                <a href="{{ route('customer.documents.reupload', $doc) }}" class="btn btn-sm btn-warning">
                                                    <i data-feather="upload"></i> Re-upload
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- All Documents -->
                <div class="card">
                    <div class="card-header">
                        <h5>All Documents</h5>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <form method="GET" class="row mb-4">
                            <div class="col-md-3">
                                <select name="case_id" class="form-select">
                                    <option value="">All Cases</option>
                                    @foreach($cases ?? [] as $case)
                                        <option value="{{ $case->id }}" {{ request('case_id') == $case->id ? 'selected' : '' }}>
                                            #{{ $case->case_number }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="type" class="form-select">
                                    <option value="">All Types</option>
                                    <option value="title" {{ request('type') === 'title' ? 'selected' : '' }}>Title</option>
                                    <option value="bill_of_sale" {{ request('type') === 'bill_of_sale' ? 'selected' : '' }}>Bill of Sale</option>
                                    <option value="drivers_license" {{ request('type') === 'drivers_license' ? 'selected' : '' }}>Driver's License</option>
                                    <option value="insurance" {{ request('type') === 'insurance' ? 'selected' : '' }}>Insurance</option>
                                    <option value="receipt" {{ request('type') === 'receipt' ? 'selected' : '' }}>Receipts</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Review</option>
                                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i data-feather="filter"></i> Filter
                                </button>
                            </div>
                        </form>

                        <!-- Documents Grid -->
                        <div class="row">
                            @forelse($documents ?? [] as $document)
                                <div class="col-md-4 col-lg-3 mb-4">
                                    <div class="card h-100 border {{ $document->status === 'rejected' ? 'border-danger' : '' }}">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                @if(in_array($document->mime_type, ['image/jpeg', 'image/png', 'image/gif']))
                                                    <img src="{{ route('api.documents.thumbnail', $document) }}"
                                                         class="img-fluid rounded" style="max-height: 120px;" alt="">
                                                @else
                                                    <i data-feather="file-text" style="width: 64px; height: 64px;"
                                                       class="text-{{ $document->status_color }}"></i>
                                                @endif
                                            </div>
                                            <h6 class="card-title">{{ $document->document_type_label }}</h6>
                                            <p class="card-text text-muted small">
                                                Case #{{ $document->case->case_number ?? '-' }}
                                            </p>
                                            <span class="badge badge-{{ $document->status_color }} mb-2">
                                                {{ ucfirst($document->status) }}
                                            </span>
                                            @if($document->rejection_reason)
                                                <p class="text-danger small mb-2">{{ $document->rejection_reason }}</p>
                                            @endif
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <div class="btn-group w-100">
                                                <a href="{{ route('api.documents.download', $document) }}"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i data-feather="download"></i>
                                                </a>
                                                <a href="{{ route('api.documents.view', $document) }}"
                                                   class="btn btn-sm btn-outline-secondary" target="_blank">
                                                    <i data-feather="eye"></i>
                                                </a>
                                                @if($document->status === 'rejected')
                                                    <a href="{{ route('customer.documents.reupload', $document) }}"
                                                       class="btn btn-sm btn-outline-warning">
                                                        <i data-feather="upload"></i>
                                                    </a>
                                                @endif
                                            </div>
                                            <small class="text-muted d-block mt-2">
                                                {{ $document->created_at->format('M j, Y') }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="text-center py-5">
                                        <i data-feather="file-text" style="width: 64px; height: 64px;" class="text-muted mb-3"></i>
                                        <h4>No Documents Found</h4>
                                        <p class="text-muted">Documents uploaded for your cases will appear here.</p>
                                    </div>
                                </div>
                            @endforelse
                        </div>

                        <!-- Pagination -->
                        @if(isset($documents) && $documents->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $documents->withQueryString()->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Document Types Info -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i data-feather="info"></i> Required Documents</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary">Required Documents</h6>
                                <ul>
                                    <li><strong>Title</strong> - Current salvage or branded title</li>
                                    <li><strong>Bill of Sale</strong> - Purchase documentation</li>
                                    <li><strong>Driver's License</strong> - Valid Florida ID</li>
                                    <li><strong>Insurance</strong> - Current Florida insurance card</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-info">Optional Documents</h6>
                                <ul>
                                    <li><strong>Repair Receipts</strong> - Parts and labor invoices</li>
                                    <li><strong>Photos</strong> - Before/after repair photos</li>
                                    <li><strong>Component Documentation</strong> - VIN tags, invoices for major parts</li>
                                </ul>
                            </div>
                        </div>
                        <div class="alert alert-info mt-3">
                            <i data-feather="info"></i>
                            <strong>Tips for document upload:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Ensure documents are clear and readable</li>
                                <li>Accepted formats: PDF, JPG, PNG (max 10MB)</li>
                                <li>All four corners of documents should be visible</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
