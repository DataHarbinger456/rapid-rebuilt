@extends('layouts.admin.master')

@section('title')Preflight @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Preflight</h3>
        @endslot
        <li class="breadcrumb-item">Admin</li>
        <li class="breadcrumb-item active">Preflight</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-9">

                {{-- Verdict first. The single question this page answers is whether
                     this deployment will work, so it is stated before the evidence
                     rather than left to be inferred from six rows. --}}
                <div class="card">
                    <div class="card-body d-flex align-items-start gap-3">
                        @if($failCount > 0)
                            <span class="text-danger" aria-hidden="true">
                                <i data-feather="x-circle" style="width: 32px; height: 32px;"></i>
                            </span>
                            <div>
                                <h5 class="mb-1">
                                    {{ $failCount }} {{ Str::plural('check', $failCount) }} failed
                                </h5>
                                <p class="mb-0 text-muted">
                                    Something a user can reach is not working on this host.
                                    Each failure below says what stops working and how to fix it.
                                </p>
                            </div>
                        @elseif($warnCount > 0)
                            <span class="text-warning" aria-hidden="true">
                                <i data-feather="alert-triangle" style="width: 32px; height: 32px;"></i>
                            </span>
                            <div>
                                <h5 class="mb-1">
                                    Working, with {{ $warnCount }} degraded
                                </h5>
                                <p class="mb-0 text-muted">
                                    Nothing is broken. The items below are running on their
                                    designed fallbacks.
                                </p>
                            </div>
                        @else
                            <span class="text-success" aria-hidden="true">
                                <i data-feather="check-circle" style="width: 32px; height: 32px;"></i>
                            </span>
                            <div>
                                <h5 class="mb-1">All checks passed</h5>
                                <p class="mb-0 text-muted">
                                    Every external dependency this app needs is present on this host.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5>Host Dependencies</h5>
                        <p class="mb-0 text-muted small">
                            Checked live on each page load. These are the things
                            {{ config('app.name') }} needs from its server that cannot be
                            installed by Composer.
                        </p>
                    </div>
                    <div class="card-body">
                        {{-- A list, not a table. The result of a check is prose of very
                             uneven length, and forcing it into a column crushed the
                             remedy text on a phone: horizontal scrolling to read the one
                             sentence that says how to fix your server is no use. The
                             status rail keeps a single shared left edge at every width. --}}
                        <ul class="list-unstyled mb-0">
                            @foreach($checks as $check)
                                <li @class([
                                    'd-flex flex-column flex-sm-row gap-2 gap-sm-3 py-3',
                                    'border-bottom' => ! $loop->last,
                                    'pt-0' => $loop->first,
                                ])>
                                    {{-- min-width, not flex-basis: basis works on the main
                                         axis, so on a stacked phone layout it reserved 7rem
                                         of empty height above every result. --}}
                                    <div class="flex-shrink-0" style="min-width: 7rem">
                                        {{-- Icon plus word. Never colour alone. --}}
                                        <span class="badge {{ $check->badgeClass() }} d-inline-flex align-items-center gap-1">
                                            <i data-feather="{{ $check->icon() }}"
                                               style="width: 14px; height: 14px;"
                                               aria-hidden="true"></i>
                                            {{ $check->label() }}
                                        </span>
                                    </div>

                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">{{ $check->name }}</h6>
                                        <p class="mb-0">{{ $check->detail }}</p>

                                        @if($check->remedy)
                                            <p class="small text-muted mb-0 mt-1">{{ $check->impact }}</p>
                                            <p class="small mb-0 mt-1">
                                                <span class="fw-semibold">Fix:</span> {{ $check->remedy }}
                                            </p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-xl-3">
                <div class="card">
                    <div class="card-header">
                        <h5>On the server</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">
                            The same checks run from the command line, which is the version to
                            use in a deploy pipeline. It exits non-zero if anything fails.
                        </p>
                        <pre class="bg-light border rounded p-2 mb-0"><code>php artisan preflight</code></pre>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5>Reading this page</h5>
                    </div>
                    <div class="card-body">
                        <dl class="mb-0 small">
                            <dt><span class="badge bg-success text-white">Pass</span></dt>
                            <dd class="text-muted">Present and working.</dd>

                            <dt class="mt-2"><span class="badge bg-warning text-dark">Degraded</span></dt>
                            <dd class="text-muted">
                                Missing, but the feature has a designed fallback and still works.
                            </dd>

                            <dt class="mt-2"><span class="badge bg-danger text-white">Fail</span></dt>
                            <dd class="mb-0 text-muted">
                                Missing, and something a user can reach is now broken.
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
