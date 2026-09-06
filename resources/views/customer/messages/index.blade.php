@extends('layouts.customer.master')

@section('title')Messages @endsection

@push('css')
<style>
/* Unread accent. Deliberately not .bg-light: the theme pairs that class with
   color:#fff (style.css:1762), which renders white text on a pale grey row. */
.message-unread {
    border-left: 3px solid #7366ff;
    background-color: #fbfbfe;
}
</style>
@endpush

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
                    @if(session('success'))
                        <div class="alert alert-success mb-0">{{ session('success') }}</div>
                    @endif
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Your Messages</h5>
                        <a href="{{ route('customer.messages.create') }}" class="btn btn-primary">
                            <i data-feather="plus"></i> New Message
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse($messages as $message)
                                @php($isIncoming = $message->recipient_id === auth()->id())
                                <li class="list-group-item {{ $isIncoming && !$message->read_at ? 'message-unread' : '' }}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                {{-- Unread only means anything for messages sent TO you. --}}
                                                @if($isIncoming && !$message->read_at)
                                                    <span class="badge badge-primary me-2">New</span>
                                                @endif
                                                {{ $message->sender_id === auth()->id() ? 'You' : ($message->sender?->full_name ?? 'Support') }}
                                            </h6>
                                            <p class="text-muted mb-1">{{ Str::limit($message->message, 100) }}</p>
                                            <small class="text-muted">
                                                {{ $message->created_at->diffForHumans() }}
                                                @if($message->userCase)
                                                    | Case
                                                    <a href="{{ route('customer.cases.show', $message->userCase) }}">{{ $message->userCase->case_number }}</a>
                                                @endif
                                            </small>
                                        </div>
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
                    @if($messages->hasPages())
                        <div class="card-footer">
                            {{ $messages->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
