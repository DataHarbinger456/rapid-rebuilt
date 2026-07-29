@extends('layouts.customer.master')

@section('title')Messages @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Messages</h3>
        @endslot
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Messages</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Your Messages</h5>
                        <a href="{{ route('customer.messages.create') }}" class="btn btn-primary">
                            <i data-feather="plus"></i> New Message
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse($messages ?? [] as $message)
                                <li class="list-group-item {{ !$message->read_at ? 'bg-light' : '' }}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                @if(!$message->read_at)
                                                    <span class="badge badge-primary me-2">New</span>
                                                @endif
                                                {{ $message->subject }}
                                            </h6>
                                            <p class="text-muted mb-1">{{ Str::limit($message->content, 100) }}</p>
                                            <small class="text-muted">
                                                {{ $message->created_at->diffForHumans() }}
                                                @if($message->case)
                                                    | Case #{{ $message->case->case_number }}
                                                @endif
                                            </small>
                                        </div>
                                        <a href="#" class="btn btn-sm btn-outline-primary">
                                            <i data-feather="eye"></i>
                                        </a>
                                    </div>
                                </li>
                            @empty
                                <li class="list-group-item text-center py-5">
                                    <i data-feather="message-circle" style="width: 48px; height: 48px;" class="text-muted mb-3"></i>
                                    <p class="text-muted mb-3">No messages yet</p>
                                    <a href="{{ route('customer.support') }}" class="btn btn-primary">
                                        Contact Support
                                    </a>
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
