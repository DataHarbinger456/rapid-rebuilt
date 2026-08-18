@extends('layouts.admin.master')

@section('title')Generate Documents @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Generate Documents</h3>
        @endslot
        <li class="breadcrumb-item">Inspector</li>
        <li class="breadcrumb-item active">Documents</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-8">
                <!-- Documents List -->
                <div class="card">
                    <div class="card-header">
                        <h5>HSMV Documents</h5>
                        <span>Generate official Florida DMV documents</span>
                    </div>
                    <div class="card-body">
                        {{-- PACKAGE_FORMS is the single source of truth: the same
                             constant drives the packet manifest and this list,
                             so a form can never be named one thing in the
                             folder and another on screen. This view used to
                             carry its own copy and mislabelled four of five
                             forms while omitting the 82042 entirely. --}}
                        @php $forms = \App\Services\PDFGeneratorService::PACKAGE_FORMS; @endphp

                        @foreach($forms as $formId => $form)
                            @php $generated = isset($generated_documents[$formId]); @endphp
                            <div class="d-flex align-items-center justify-content-between p-3 mb-2 rounded {{ $generated ? 'bg-light' : 'border' }}">
                                <div>
                                    <h6 class="mb-0">{{ $form['name'] }}</h6>
                                    <small class="text-muted">{{ $form['desc'] }}</small>
                                    <br>
                                    <span class="badge badge-secondary">{{ $formId }}</span>
                                </div>
                                <div>
                                    @if($generated)
                                        <span class="text-success me-2"><i data-feather="check-circle"></i> Generated</span>
                                        <a href="{{ $generated_documents[$formId]['url'] ?? '#' }}" class="btn btn-outline-primary btn-sm" target="_blank">
                                            <i data-feather="download"></i> Download
                                        </a>
                                    @else
                                        @if($can_generate)
                                            <button class="btn btn-primary btn-sm generate-doc-btn" data-form="{{ $formId }}">
                                                <i data-feather="file-plus"></i> Generate
                                            </button>
                                        @else
                                            <span class="text-muted">
                                                <i data-feather="alert-circle"></i> Missing data
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Generate All -->
                @if($can_generate)
                    <div class="card">
                        <div class="card-body text-center">
                            {{-- The endpoint lives in routes/api.php and is
                                 unnamed; route('api.inspections.generate-documents')
                                 does not exist and 500s the whole page. This view
                                 never rendered until that was fixed. --}}
                            <form action="{{ url('/api/inspections/' . $inspection->id . '/generate-documents') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i data-feather="package"></i> Generate All Documents
                                </button>
                            </form>
                            <p class="text-muted mt-2 mb-0">This will generate all required HSMV forms as a package</p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Inspection Passed</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i data-feather="check-circle" style="width: 48px; height: 48px; color: #28a745;"></i>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Case #:</span>
                            <strong>{{ $case->case_number }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Inspector:</span>
                            <strong>{{ auth()->user()->name }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Completed:</span>
                            <strong>{{ $inspection->completed_at ? $inspection->completed_at->format('M j, Y') : 'N/A' }}</strong>
                        </div>
                    </div>
                </div>

                @if(count($generated_documents) > 0)
                    <div class="card">
                        <div class="card-header">
                            <h5>Print Package</h5>
                        </div>
                        <div class="card-body">
                            <a href="{{ route('inspector.print', $inspection) }}" class="btn btn-primary w-100">
                                <i data-feather="printer"></i> Print All Documents
                            </a>
                        </div>
                    </div>
                @endif

                <div class="card">
                    <div class="card-header">
                        <h5>Navigation</h5>
                    </div>
                    <div class="card-body d-grid gap-2">
                        <a href="{{ route('inspector.summary', $inspection) }}" class="btn btn-outline-secondary">
                            <i data-feather="file-text"></i> Back to Summary
                        </a>
                        <a href="{{ route('inspector.dashboard') }}" class="btn btn-outline-primary">
                            <i data-feather="home"></i> Return to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
