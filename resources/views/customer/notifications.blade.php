@extends('layouts.customer.master')

@section('title')Notifications @endsection

@push('css')
<style>
/* Unread accent. Deliberately not .bg-light: the theme pairs that class with
   color:#fff (style.css:1762), which renders white text on a pale grey row. */
.notification-unread {
    border-left: 3px solid #7366ff;
    background-color: #fbfbfe;
}
.notification-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background-color: #f5f7fb;
    flex-shrink: 0;
}
.notification-icon svg {
    width: 18px;
    height: 18px;
}

/* On a phone the inline Mark read button steals ~40% of the row and squeezes
   the text into two-word lines; drop it under the message instead. */
@media (max-width: 575.98px) {
    .notification-row {
        flex-direction: column;
    }
    .notification-row .notification-action {
        margin-left: 52px; /* line up under the text, past the icon */
        margin-top: 0.5rem;
    }
}
</style>
@endpush

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Notifications</h3>
        @endslot
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Notifications</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            Your Notifications
                            @if($unreadCount > 0)
                                <span class="badge badge-primary ms-2">{{ $unreadCount }} unread</span>
                            @endif
                        </h5>

                        @if($unreadCount > 0)
                            <form action="{{ route('customer.notifications.read-all') }}" method="POST" class="mb-0">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                    <i data-feather="check-square"></i> Mark all read
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse($notifications as $notification)
                                <li class="list-group-item {{ $notification->read_at ? '' : 'notification-unread' }}">
                                    <div class="d-flex justify-content-between align-items-start notification-row">
                                        <div class="d-flex">
                                            <span class="notification-icon me-3">
                                                <i data-feather="{{ $notification->icon }}"
                                                   class="font-{{ $notification->color }}"></i>
                                            </span>
                                            <div>
                                                <h6 class="mb-1">
                                                    @unless($notification->read_at)
                                                        <span class="badge badge-primary me-2">New</span>
                                                    @endunless
                                                    {{ $notification->title ?? $notification->notification }}
                                                </h6>

                                                {{-- title and notification are separate columns; only
                                                     repeat the body when there is a distinct title. --}}
                                                @if($notification->title)
                                                    <p class="text-muted mb-1">{{ $notification->notification }}</p>
                                                @endif

                                                <small class="text-muted">
                                                    {{ $notification->created_at->diffForHumans() }}
                                                    @if($notification->userCase)
                                                        | Case
                                                        <a href="{{ route('customer.cases.show', $notification->userCase) }}">{{ $notification->userCase->case_number }}</a>
                                                    @endif
                                                    @if($notification->action_url)
                                                        | <a href="{{ $notification->action_url }}">View details</a>
                                                    @endif
                                                </small>
                                            </div>
                                        </div>

                                        @unless($notification->read_at)
                                            <form action="{{ route('customer.notifications.read', $notification) }}"
                                                  method="POST" class="mb-0 ms-3 notification-action">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-light text-nowrap">
                                                    <i data-feather="check"></i> Mark read
                                                </button>
                                            </form>
                                        @endunless
                                    </div>
                                </li>
                            @empty
                                <li class="list-group-item text-center py-5">
                                    <i data-feather="bell" style="width: 48px; height: 48px;" class="text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No notifications yet</p>
                                </li>
                            @endforelse
                        </ul>
                    </div>

                    @if($notifications->hasPages())
                        <div class="card-footer">
                            {{ $notifications->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
