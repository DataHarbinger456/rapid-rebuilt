{{--
    Document readiness meter.

    @include('partials.document-readiness', ['readiness' => $readiness, 'variant' => 'customer'])

    $readiness — App\Support\DocumentReadiness
    $variant   — 'customer' (rows link to upload / re-upload) or 'admin' (read only;
                 approve/reject stay in the documents table below the meter).
    $case      — required by the 'customer' variant to build the upload links.
--}}
@php($variant = $variant ?? 'admin')

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i data-feather="check-square"></i> Document Readiness</h5>
        <span class="badge bg-{{ $readiness->stateColor() }}">{{ $readiness->stateLabel() }}</span>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>{{ $readiness->headline() }}</strong>
            @if ($readiness->requiredTotal > 0)
                <span class="text-muted small">{{ $readiness->percent }}%</span>
            @endif
        </div>

        @if ($readiness->requiredTotal > 0)
            <div class="progress mb-3" style="height: 10px;">
                <div class="progress-bar bg-{{ $readiness->stateColor() }}"
                     role="progressbar"
                     style="width: {{ $readiness->percent }}%;"
                     aria-valuenow="{{ $readiness->percent }}"
                     aria-valuemin="0"
                     aria-valuemax="100"></div>
            </div>
        @endif

        <ul class="list-group list-group-flush">
            @foreach ($readiness->requiredItems() as $item)
                <li class="list-group-item px-0 d-flex justify-content-between align-items-start">
                    <div>
                        <strong>{{ $item->requirement->label }}</strong>
                        @if ($item->documentCount > 1)
                            <span class="text-muted small">({{ $item->documentCount }} files)</span>
                        @endif
                        @if ($variant === 'customer' && $item->requirement->description)
                            <br><small class="text-muted">{{ $item->requirement->description }}</small>
                        @endif
                        @if ($item->isRejected() && $item->rejectionReason)
                            <br><small class="text-danger">{{ $item->rejectionReason }}</small>
                        @endif
                    </div>
                    <div class="text-nowrap ms-3">
                        <span class="badge bg-{{ $item->stateColor() }}">{{ $item->stateLabel() }}</span>
                        @if ($variant === 'customer' && $item->isRejected() && $item->latestDocument)
                            <a href="{{ route('customer.documents.reupload', $item->latestDocument) }}"
                               class="btn btn-sm btn-outline-warning ms-1">Re-upload</a>
                        @elseif ($variant === 'customer' && $item->isMissing())
                            <a href="{{ route('customer.cases.documents', $case) }}"
                               class="btn btn-sm btn-outline-primary ms-1">Upload</a>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>

        @php($optional = $readiness->optionalItems())
        @if (count($optional) > 0)
            <p class="text-muted small mt-3 mb-1">Optional — these do not hold up your inspection.</p>
            <ul class="list-group list-group-flush">
                @foreach ($optional as $item)
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center text-muted">
                        <span class="small">{{ $item->requirement->label }}</span>
                        <span class="badge bg-light text-dark">{{ $item->stateLabel() }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
