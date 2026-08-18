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
                                <strong>{{ count($conflicts) }} field(s) differ from the case record.</strong>
                                @if(array_key_exists('title_state', $conflicts))
                                    <div class="mt-1">
                                        The issuing state differs. This decides whether the HSMV 82040 is
                                        filed as an <em>Original</em> or a <em>Transfer</em>.
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if($vinWarning)
                            <div class="alert alert-danger">
                                <strong>VIN failed checksum validation.</strong>
                                <div class="mt-1">{{ $vinWarning }}</div>
                            </div>
                        @endif

                        @if($vinDecode && $vinDecode['status'] === \App\Services\Extraction\VinDecodeCheck::MISMATCHED)
                            <div class="alert alert-danger">
                                <strong>The VIN decodes to a different vehicle.</strong>
                                <div class="mt-1">
                                    Decoding the VIN gives
                                    <strong>{{ collect($vinDecode['decoded'])->filter()->implode(' ') }}</strong>.
                                    Either the VIN or the vehicle details were misread — check both against the
                                    document before confirming.
                                </div>
                            </div>
                        @elseif($vinDecode && $vinDecode['status'] === \App\Services\Extraction\VinDecodeCheck::UNAVAILABLE)
                            {{-- Softer, and deliberately not an alert: the decoder being unreachable
                                 says nothing about the document, and must not look like a finding. --}}
                            <p class="text-muted small mb-3">
                                The VIN decoder could not confirm this VIN, so the vehicle details below have
                                not been cross-checked. This does not indicate a problem with the document.
                            </p>
                        @endif

                        <form method="POST" action="{{ route('admin.extractions.confirm', [$case->id, $extraction->id]) }}">
                            @csrf

                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width: 24%">Field</th>
                                            <th style="width: 42%">Value</th>
                                            <th style="width: 34%">Decision</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($extraction->fields as $field)
                                            @php
                                                $key = $field->field_key;
                                                $conflict = $conflicts[$key] ?? null;
                                                $current = $field->confirmed_value ?? $field->ai_value;
                                                $failsChecksum = $key === 'vin' && $vinWarning !== null;
                                                $decodeMismatch = $vinDecode['mismatches'][$key] ?? null;
                                                // The VIN row carries the decoder's verdict too: a mismatch
                                                // means either the VIN or these fields were misread, and the
                                                // reviewer has to look at both.
                                                $vinDecodeFlag = $key === 'vin'
                                                    && ($vinDecode['status'] ?? null) === \App\Services\Extraction\VinDecodeCheck::MISMATCHED;
                                                $flagged = $failsChecksum || $vinDecodeFlag || $decodeMismatch !== null;
                                            @endphp
                                            <tr @class([
                                                'table-warning' => $conflict !== null && ! $flagged,
                                                'table-danger' => $flagged,
                                            ])>
                                                <td>
                                                    <strong>{{ $labels[$key] ?? $key }}</strong>
                                                    @if($failsChecksum)
                                                        <div class="small text-danger fw-bold">
                                                            Fails ISO 3779 check digit &mdash; verify character by character
                                                        </div>
                                                    @endif
                                                    @if($vinDecodeFlag)
                                                        <div class="small text-danger fw-bold">
                                                            Decodes to {{ collect($vinDecode['decoded'])->filter()->implode(' ') }}
                                                        </div>
                                                    @endif
                                                    @if($decodeMismatch)
                                                        <div class="small text-danger">
                                                            VIN decodes to: {{ $decodeMismatch['decoded'] }}
                                                        </div>
                                                    @endif
                                                    @if($field->ai_value === null)
                                                        <div class="text-muted small">Not found on the document</div>
                                                    @endif
                                                    @if($conflict !== null)
                                                        {{-- Both sides are named. Showing only the case value left
                                                             reviewers unsure which of the two it referred to, and
                                                             "customer entered" was not even true for year, make and
                                                             model: those are read-only at intake and come from the
                                                             VIN decoder, not from anything the customer typed. --}}
                                                        <div class="small text-danger">
                                                            AI read <strong>{{ $field->ai_value }}</strong>,
                                                            on file <strong>{{ $conflict }}</strong>
                                                        </div>
                                                    @endif
                                                    @if(\App\Services\Extraction\ExtractionConfirmer::vehicleColumnFor($key) === null)
                                                        <div class="text-muted small">Cross-check only &mdash; not saved</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{-- A VIN has to be compared to the document one character at a
                                                         time, so it is set in a monospace face and given room for all
                                                         17 characters rather than being clipped. --}}
                                                    <input type="text"
                                                           @class(['form-control', 'font-monospace' => $key === 'vin'])
                                                           @if($key === 'vin') style="min-width: 20ch; letter-spacing: .04em;" @endif
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
