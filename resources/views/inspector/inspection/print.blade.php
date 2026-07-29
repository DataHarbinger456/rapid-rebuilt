@extends('layouts.admin.master')

@section('title')Print Documents @endsection

@push('css')
<style>
    @media print {
        .no-print {
            display: none !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
    }
</style>
@endpush

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Print Package</h3>
        @endslot
        <li class="breadcrumb-item">Inspector</li>
        <li class="breadcrumb-item active">Print</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row no-print mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Document Package for Case #{{ $case->case_number }}</h5>
                            <small class="text-muted">{{ count($documents) }} documents ready to print</small>
                        </div>
                        <div>
                            <button onclick="window.print()" class="btn btn-primary btn-lg">
                                <i data-feather="printer"></i> Print All
                            </button>
                            <a href="{{ route('inspector.documents', $inspection) }}" class="btn btn-outline-secondary">
                                <i data-feather="arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Document List -->
        <div class="row">
            <div class="col-12">
                @forelse($documents as $docType => $doc)
                    <div class="card mb-4">
                        <div class="card-header no-print">
                            <h5>{{ $doc['name'] ?? $docType }}</h5>
                        </div>
                        <div class="card-body">
                            @if(isset($doc['url']))
                                <iframe src="{{ $doc['url'] }}" style="width: 100%; height: 800px; border: none;"></iframe>
                            @else
                                <div class="text-center py-5">
                                    <i data-feather="file-text" style="width: 48px; height: 48px; color: #ddd;"></i>
                                    <p class="mt-2 text-muted">Document preview not available</p>
                                    @if(isset($doc['download_url']))
                                        <a href="{{ $doc['download_url'] }}" class="btn btn-primary" download>
                                            <i data-feather="download"></i> Download PDF
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i data-feather="alert-circle" style="width: 64px; height: 64px; color: #ddd;"></i>
                            <h5 class="mt-3 text-muted">No Documents Generated</h5>
                            <p class="text-muted">Please generate documents first before printing.</p>
                            <a href="{{ route('inspector.documents', $inspection) }}" class="btn btn-primary">
                                <i data-feather="file-plus"></i> Generate Documents
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

@endsection
