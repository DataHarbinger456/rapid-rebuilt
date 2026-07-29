@extends('layouts.customer.master')

@section('title')My Cases @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>My Cases</h3>
        @endslot
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">My Cases</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>All Cases</h5>
                        <a href="{{ route('customer.cases.create') }}" class="btn btn-primary">
                            <i data-feather="plus"></i> Start New Case
                        </a>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <form method="GET" class="row mb-4">
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                                    <option value="pending_payment" {{ request('status') === 'pending_payment' ? 'selected' : '' }}>Pending Payment</option>
                                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                                    <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" placeholder="Search by VIN or case number..."
                                       value="{{ request('search') }}">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i data-feather="search"></i> Search
                                </button>
                            </div>
                        </form>

                        <!-- Cases List -->
                        @forelse($cases ?? [] as $case)
                            <div class="card mb-3 border {{ $case->hasRequiredAction ? 'border-warning' : '' }}">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-3">
                                            <h6 class="mb-1">Case #{{ $case->case_number }}</h6>
                                            <span class="badge badge-{{ $case->status_color }}">{{ $case->status_label }}</span>
                                        </div>
                                        <div class="col-md-4">
                                            @if($case->vehicle)
                                                <strong>{{ $case->vehicle->year }} {{ $case->vehicle->make }} {{ $case->vehicle->model }}</strong>
                                                <p class="mb-0 text-muted">VIN: {{ $case->vehicle->vin }}</p>
                                            @else
                                                <span class="text-muted">Vehicle information not added</span>
                                            @endif
                                        </div>
                                        <div class="col-md-2 text-center">
                                            <small class="text-muted d-block">Progress</small>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-primary" style="width: {{ $case->progress_percent ?? 0 }}%"></div>
                                            </div>
                                            <small>{{ $case->progress_percent ?? 0 }}%</small>
                                        </div>
                                        <div class="col-md-3 text-end">
                                            @if($case->hasRequiredAction)
                                                <span class="badge badge-warning mb-2 d-block">Action Required</span>
                                            @endif
                                            <a href="{{ route('customer.cases.show', $case) }}" class="btn btn-outline-primary btn-sm">
                                                <i data-feather="eye"></i> View
                                            </a>
                                            @if($case->status === 'draft')
                                                <a href="{{ route('customer.cases.edit', $case) }}" class="btn btn-outline-secondary btn-sm">
                                                    <i data-feather="edit"></i> Continue
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if($case->hasRequiredAction)
                                    <div class="card-footer bg-warning-light">
                                        <i data-feather="alert-circle" class="text-warning"></i>
                                        <strong>{{ $case->requiredAction['title'] ?? 'Action needed' }}</strong>
                                        <a href="{{ $case->requiredAction['url'] ?? '#' }}" class="btn btn-sm btn-warning float-end">
                                            {{ $case->requiredAction['button'] ?? 'Take Action' }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-5">
                                <i data-feather="briefcase" style="width: 64px; height: 64px;" class="text-muted mb-3"></i>
                                <h4>No Cases Found</h4>
                                <p class="text-muted">Start your first case to begin the rebuilt title process.</p>
                                <a href="{{ route('customer.cases.create') }}" class="btn btn-primary btn-lg">
                                    <i data-feather="plus-circle"></i> Start New Case
                                </a>
                            </div>
                        @endforelse

                        <!-- Pagination -->
                        @if(isset($cases) && $cases->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $cases->withQueryString()->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
