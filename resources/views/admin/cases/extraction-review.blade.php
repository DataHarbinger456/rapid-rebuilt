@extends('layouts.admin.master')

@section('title')Review Extracted Data @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Review Extracted Data</h3>
        @endslot
        <li class="breadcrumb-item">Admin</li>
        <li class="breadcrumb-item"><a href="{{ route('admin.cases.index') }}">Cases</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.cases.show', $case->id) }}">{{ $case->case_number }}</a></li>
        <li class="breadcrumb-item active">Extraction</li>
    @endcomponent

    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($extraction->status === \App\Models\DocumentExtraction::STATUS_FAILED)
            <div class="alert alert-danger">
                <strong>This document could not be read.</strong>
                <div class="mt-1">{{ $extraction->error_message }}</div>
            </div>
        @endif

        <div class="row">
            <!-- Source document -->
            <div class="col-xl-5">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>{{ $document?->document_type_label ?? 'Source Document' }}</h5>
                        @if($document)
                            <a href="{{ route('api.documents.view', $document) }}" target="_blank" rel="noopener"
                               class="btn btn-sm btn-outline-primary">Open full size</a>
                        @endif
                    </div>
                    <div class="card-body text-center">
                        @if($document && $document->is_image)
                            <img src="{{ route('api.documents.view', $document) }}"
                                 alt="{{ $document->original_name }}"
                                 class="img-fluid border rounded">
                        @elseif($document)
                            <embed src="{{ route('api.documents.view', $document) }}"
                                   type="application/pdf" width="100%" height="600px">
                        @else
                            <p class="text-muted mb-0">The source document is no longer available.</p>
                        @endif
                    </div>
                    <div class="card-footer text-muted small">
                        Read by {{ $extraction->provider }} / {{ $extraction->model }}
                        @if($extraction->completed_at)
                            on {{ $extraction->completed_at->format('M j, Y g:ia') }}
                        @endif
                    </div>
                </div>
            </div>

            <!-- Field confirmation -->
            <div class="col-xl-7">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5>Confirm Each Field</h5>
                            <p class="mb-0 text-muted small">
                                Nothing reaches the state form until every field below is confirmed,
                                corrected or rejected.
                            </p>
                        </div>
                        <form method="POST" action="{{ route('admin.extractions.retry', [$case->id, $extraction->id]) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Read again</button>
                        </form>
                    </div>

                    <div class="card-body">
                        @if($conflicts)
                            <div class="alert alert-warning">
                                <strong>{{ count($conflicts) }} field(s) disagree with what the customer entered.</strong>
                                @if(array_key_exists('title_state', $conflicts))
                                    <div class="mt-1">
                                        The issuing state differs. This decides whether the HSMV 82040 is
                                        filed as an <em>Original</em> or a <em>Transfer</em>.
                                    </div>
                                @endif
                            </div>
                        @endif

                        <form method="POST" action="{{ route('admin.extractions.confirm', [$case->id, $extraction->id]) }}">
                            @csrf

                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width: 26%">Field</th>
                                            <th style="width: 38%">Value</th>
                                            <th style="width: 36%">Decision</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($extraction->fields as $field)
                                            @php
                                                $key = $field->field_key;
                                                $conflict = $conflicts[$key] ?? null;
                                                $current = $field->confirmed_value ?? $field->ai_value;
                                            @endphp
                                            <tr @class(['table-warning' => $conflict !== null])>
                                                <td>
                                                    <strong>{{ $labels[$key] ?? $key }}</strong>
                                                    @if($field->ai_value === null)
                                                        <div class="text-muted small">Not found on the document</div>
                                                    @endif
                                                    @if($conflict !== null)
                                                        <div class="small text-danger">
                                                            Customer entered: {{ $conflict }}
                                                        </div>
                                                    @endif
                                                    @if(\App\Services\Extraction\ExtractionConfirmer::vehicleColumnFor($key) === null)
                                                        <div class="text-muted small">Cross-check only &mdash; not saved</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <input type="text"
                                                           class="form-control"
                                                           name="fields[{{ $key }}][value]"
                                                           value="{{ old('fields.' . $key . '.value', $current) }}"
                                                           maxlength="255">
                                                </td>
                                                <td>
                                                    @foreach([
                                                        \App\Models\DocumentExtractionField::STATUS_CONFIRMED => 'Confirm',
                                                        \App\Models\DocumentExtractionField::STATUS_CORRECTED => 'Correct',
                                                        \App\Models\DocumentExtractionField::STATUS_REJECTED => 'Reject',
                                                    ] as $status => $label)
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input"
                                                                   type="radio"
                                                                   id="{{ $key }}_{{ $status }}"
                                                                   name="fields[{{ $key }}][status]"
                                                                   value="{{ $status }}"
                                                                   required
                                                                   @checked(old('fields.' . $key . '.status', $field->status) === $status)>
                                                            <label class="form-check-label small" for="{{ $key }}_{{ $status }}">
                                                                {{ $label }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if($extraction->fields->isEmpty())
                                <p class="text-muted">This extraction produced no fields to review.</p>
                            @else
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.cases.show', $case->id) }}" class="btn btn-outline-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">Save to vehicle record</button>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
