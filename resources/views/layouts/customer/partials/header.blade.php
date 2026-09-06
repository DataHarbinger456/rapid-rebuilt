<div class="page-main-header">
    <div class="main-header-right row m-0">
        <div class="main-header-left">
            <div class="logo-wrapper">
                <a href="{{ route('customer.dashboard') }}">
                    <img class="img-fluid" src="{{ asset('assets/images/logo/logo.png') }}" alt="Rapid Rebuilt">
                </a>
            </div>
            <div class="dark-logo-wrapper">
                <a href="{{ route('customer.dashboard') }}">
                    <img class="img-fluid" src="{{ asset('assets/images/logo/dark-logo.png') }}" alt="Rapid Rebuilt">
                </a>
            </div>
            <div class="toggle-sidebar">
                <i class="status_toggle middle" data-feather="align-center" id="sidebar-toggle"></i>
            </div>
        </div>

        <div class="nav-right col pull-right right-menu p-0">
            <ul class="nav-menus">
                <!-- Notifications -->
                <li class="onhover-dropdown">
                    <div class="notification-box">
                        <i data-feather="bell"></i>
                        @if(isset($unreadNotifications) && $unreadNotifications > 0)
                            <span class="dot-animated"></span>
                        @endif
                    </div>
                    <ul class="notification-dropdown onhover-show-div">
                        <li>
                            <p class="f-w-700 mb-0">Notifications
                                @if(isset($unreadNotifications) && $unreadNotifications > 0)
                                    <span class="pull-right badge badge-primary badge-pill">{{ $unreadNotifications }}</span>
                                @endif
                            </p>
                        </li>
                        @forelse($notifications ?? [] as $notification)
                            <li class="noti-{{ $notification->read_at ? '' : 'primary' }}">
                                <div class="media">
                                    <span class="notification-bg bg-light-{{ $notification->color }}">
                                        <i data-feather="{{ $notification->icon }}"></i>
                                    </span>
                                    <div class="media-body">
                                        {{-- There is no `message` attribute: the columns are
                                             `title` and `notification`. --}}
                                        <p>{{ $notification->title ?? $notification->notification }}</p>
                                        <span>{{ $notification->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="txt-dark text-center py-3">No new notifications</li>
                        @endforelse
                        <li>
                            <a class="btn btn-primary" href="{{ route('customer.notifications') ?? '#' }}">View All</a>
                        </li>
                    </ul>
                </li>

                <!-- Messages -->
                <li class="onhover-dropdown">
                    <i data-feather="message-square"></i>
                    @if(isset($unreadMessages) && $unreadMessages > 0)
                        <span class="dot-animated"></span>
                    @endif
                    <ul class="chat-dropdown onhover-show-div">
                        <li>
                            <p class="f-w-700 mb-0">Messages
                                @if(isset($unreadMessages) && $unreadMessages > 0)
                                    <span class="pull-right badge badge-primary badge-pill">{{ $unreadMessages }}</span>
                                @endif
                            </p>
                        </li>
                        @forelse($messages ?? [] as $message)
                            <li>
                                <div class="media">
                                    <div class="media-body">
                                        <span>{{ $message->subject }}</span>
                                        <p class="f-12 light-font">{{ Str::limit($message->content, 50) }}</p>
                                    </div>
                                    <p class="f-12 font-{{ $message->read_at ? 'secondary' : 'primary' }}">
                                        {{ $message->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </li>
                        @empty
                            <li class="text-center py-3">No messages</li>
                        @endforelse
                        <li class="text-center">
                            <a class="btn btn-primary" href="{{ route('customer.messages') ?? '#' }}">View All</a>
                        </li>
                    </ul>
                </li>

                <!-- User Profile -->
                <li class="onhover-dropdown p-0">
                    <div class="media profile-media">
                        <img class="rounded-circle" src="{{ auth()->user()->avatar ?? asset('assets/images/dashboard/profile.jpg') }}" alt="">
                        <div class="media-body">
                            <span>{{ auth()->user()->name ?? 'Customer' }}</span>
                            <p class="mb-0 font-roboto">Customer <i class="middle fa fa-angle-down"></i></p>
                        </div>
                    </div>
                    <ul class="profile-dropdown onhover-show-div">
                        <li><a href="{{ route('customer.profile') ?? '#' }}"><i data-feather="user"></i><span>My Profile</span></a></li>
                        <li><a href="{{ route('customer.settings') ?? '#' }}"><i data-feather="settings"></i><span>Settings</span></a></li>
                        <li>
                            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i data-feather="log-out"></i><span>Log out</span>
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>

        {{-- Below lg the theme hides .nav-right > ul entirely (visibility:hidden)
             until .open is added, which script.js binds to this button. Every
             other layout's header ships it; without it the bell and profile
             menu are unreachable on a phone. --}}
        <div class="d-lg-none mobile-toggle pull-right w-auto"><i data-feather="more-horizontal"></i></div>
    </div>
</div>
