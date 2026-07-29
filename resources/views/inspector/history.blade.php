@extends('layouts.admin.master')

@section('title')Inspection History @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Inspection History</h3>
        @endslot
        <li class="breadcrumb-item">Inspector</li>
        <li class="breadcrumb-item active">History</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Completed Inspections</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Case #</th>
                                        <th>Customer</th>
                                        <th>Vehicle</th>
                                        <th>Result</th>
                                        <th>Duration</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($inspections as $inspection)
                                        <tr>
                                            <td>
                                                {{ $inspection->completed_at ? $inspection->completed_at->format('M j, Y') : 'N/A' }}
                                                <br>
                                                <small class="text-muted">{{ $inspection->completed_at ? $inspection->completed_at->format('g:i A') : '' }}</small>
                                            </td>
                                            <td>
                                                <strong>{{ $inspection->userCase->case_number ?? 'N/A' }}</strong>
                                            </td>
                                            <td>{{ $inspection->userCase->user->name ?? 'N/A' }}</td>
                                            <td>
                                                @if($inspection->userCase->vehicle)
                                                    {{ $inspection->userCase->vehicle->year }}
                                                    {{ $inspection->userCase->vehicle->make }}
                                                    {{ $inspection->userCase->vehicle->model }}
                                                    <br>
                                                    <small class="text-muted font-monospace">{{ $inspection->userCase->vehicle->vin }}</small>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($inspection->result === 'passed')
                                                    <span class="badge badge-success">PASSED</span>
                                                @else
                                                    <span class="badge badge-danger">FAILED</span>
                                                @endif
                                            </td>
                                            <td>{{ $inspection->duration_minutes ?? '-' }} min</td>
                                            <td>
                                                <a href="{{ route('inspector.summary', $inspection) }}" class="btn btn-outline-primary btn-sm">
                                                    <i data-feather="eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                No completed inspections found
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if($inspections->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $inspections->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
