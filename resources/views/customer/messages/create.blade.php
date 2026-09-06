@extends('layouts.customer.master')

@section('title')New Message @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>New Message</h3>
        @endslot
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customer.messages') }}">Messages</a></li>
        <li class="breadcrumb-item active">New</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Send us a message</h5>
                    </div>
                    <div class="card-body">
                        @if($cases->isEmpty())
                            {{-- Every message hangs off a case (user_case_id is NOT NULL),
                                 so there is nothing to compose against yet. --}}
                            <div class="text-center py-5">
                                <i data-feather="message-circle" style="width: 48px; height: 48px;" class="text-muted mb-3"></i>
                                <p class="text-muted mb-3">Messages are attached to a case. Start a case first, then ask us anything about it.</p>
                                <a href="{{ route('customer.cases.create') }}" class="btn btn-primary">
                                    <i data-feather="plus"></i> Start a Case
                                </a>
                            </div>
                        @else
                            <form action="{{ route('customer.messages.store') }}" method="POST">
                                @csrf

                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label class="form-label" for="caseSelect">Case *</label>
                                        <select name="case_id" id="caseSelect"
                                                class="form-select @error('case_id') is-invalid @enderror" required>
                                            <option value="">Select a case…</option>
                                            @foreach($cases as $case)
                                                <option value="{{ $case->id }}"
                                                    @selected((int) old('case_id', $selectedCase?->id) === $case->id)>
                                                    {{ $case->case_number }}
                                                    @if($case->vehicle)
                                                        — {{ $case->vehicle->year }} {{ $case->vehicle->make }} {{ $case->vehicle->model }}
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('case_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label class="form-label" for="messageInput">Message *</label>
                                        <textarea name="message" id="messageInput" rows="6" maxlength="5000" required
                                                  class="form-control @error('message') is-invalid @enderror"
                                                  placeholder="Describe your question or share an update…">{{ old('message') }}</textarea>
                                        @error('message')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">Our team replies by email and here in Messages.</small>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('customer.messages') }}" class="btn btn-light">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i data-feather="send"></i> Send Message
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
