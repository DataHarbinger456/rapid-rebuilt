<header class="main-nav">
    <div class="sidebar-user text-center">
        <img class="img-90 rounded-circle" src="{{asset('assets/images/dashboard/1.png')}}" alt="" />
        <div class="badge-bottom">
            <span class="badge badge-{{ Auth::user()->isAdmin() ? 'danger' : (Auth::user()->isStaff() ? 'warning' : 'primary') }}">
                {{ ucfirst(Auth::user()->user_type) }}
            </span>
        </div>
        <a href="{{ route('user-profile') }}">
            <h6 class="mt-3 f-14 f-w-600">{{ Auth::user()->user_type === 'dealer' ? Auth::user()->dealer_name : Auth::user()->full_name }}</h6>
        </a>
        <p class="mb-0 font-roboto">{{ Auth::user()->email }}</p>
    </div>
    <nav>
        <div class="main-navbar">
            <div class="left-arrow" id="left-arrow"><i data-feather="arrow-left"></i></div>
            <div id="mainnav">
                <ul class="nav-menu custom-scrollbar">
                    <li class="back-btn">
                        <div class="mobile-back text-end"><span>Back</span><i class="fa fa-angle-right ps-2" aria-hidden="true"></i></div>
                    </li>

                    {{-- Admin/Staff Menu --}}
                    @if(Auth::user()->isStaff())
                        <li class="sidebar-main-title">
                            <div><h6>Administration</h6></div>
                        </li>
                        <li class="{{ request()->routeIs('dashboard') || request()->routeIs('admin.dashboard*') ? 'active' : '' }}">
                            <a class="nav-link menu-title link-nav {{ request()->routeIs('dashboard') || request()->routeIs('admin.dashboard*') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                <i data-feather="home"></i><span>Dashboard</span>
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('admin.cases*') ? 'active' : '' }}">
                            <a class="nav-link menu-title link-nav {{ request()->routeIs('admin.cases*') ? 'active' : '' }}" href="{{ route('admin.cases.index') }}">
                                <i data-feather="briefcase"></i><span>Case Management</span>
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('admin.reports*') ? 'active' : '' }}">
                            <a class="nav-link menu-title link-nav {{ request()->routeIs('admin.reports*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">
                                <i data-feather="bar-chart-2"></i><span>Reports</span>
                            </a>
                        </li>
                        <li class="dropdown {{ routeActive('calendar-basic') }}">
                            <a class="nav-link menu-title link-nav {{ routeActive('calendar-basic') }}" href="{{ route('calendar-basic') }}">
                                <i data-feather="calendar"></i><span>Calendar</span>
                            </a>
                        </li>

                        @if(Auth::user()->isAdmin())
                            <li class="sidebar-main-title">
                                <div><h6>Settings</h6></div>
                            </li>
                            <li class="dropdown {{ request()->routeIs('admin.settings*') ? 'active' : '' }}">
                                <a class="nav-link menu-title {{ request()->routeIs('admin.settings*') ? 'active' : '' }}" href="javascript:void(0)">
                                    <i data-feather="settings"></i><span>Settings</span>
                                </a>
                                <ul class="nav-submenu menu-content" style="display: {{ request()->routeIs('admin.settings*') ? 'block' : 'none' }};">
                                    <li class="{{ request()->routeIs('admin.settings.index') ? 'active' : '' }}"><a href="{{ route('admin.settings.index') }}" class="{{ request()->routeIs('admin.settings.index') ? 'active' : '' }}">General</a></li>
                                    @if(auth()->user()->isAdmin())
                                    <li class="{{ request()->routeIs('admin.settings.users*') ? 'active' : '' }}"><a href="{{ route('admin.settings.users') }}" class="{{ request()->routeIs('admin.settings.users*') ? 'active' : '' }}">User Management</a></li>
                                    @endif
                                    <li class="{{ request()->routeIs('admin.settings.pricing*') ? 'active' : '' }}"><a href="{{ route('admin.settings.pricing') }}" class="{{ request()->routeIs('admin.settings.pricing*') ? 'active' : '' }}">Pricing</a></li>
                                    <li class="{{ request()->routeIs('admin.settings.notifications*') ? 'active' : '' }}"><a href="{{ route('admin.settings.notifications') }}" class="{{ request()->routeIs('admin.settings.notifications*') ? 'active' : '' }}">Notifications</a></li>
                                </ul>
                            </li>
                        @endif
                    @endif

                    {{-- General Menu (for all users) --}}
                    <li class="sidebar-main-title">
                        <div><h6>General</h6></div>
                    </li>
                    @if(!Auth::user()->isStaff())
                        <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <a class="nav-link menu-title link-nav {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                <i data-feather="home"></i><span>Dashboard</span>
                            </a>
                        </li>
                    @endif
                    <li class="dropdown {{ prefixActive('/form-layout') }}">
                        <a class="nav-link menu-title {{ prefixActive('/form-layout') }}" href="javascript:void(0)">
                            <i data-feather="layout"></i><span>DMV Forms</span>
                        </a>
                        <ul class="nav-submenu menu-content" style="display: {{ prefixBlock('/form-layout') }};">
                            <li class="{{ routeActive('default-form') }}"><a href="{{ route('default-form') }}" class="{{ routeActive('default-form') }}">Form 82040</a></li>
                            <li class="{{ routeActive('form-wizard') }}"><a href="{{ route('form-wizard') }}" class="{{ routeActive('form-wizard') }}">Form 82050</a></li>
                            <li class="{{ routeActive('form-wizard-two') }}"><a href="{{ route('form-wizard-two') }}" class="{{ routeActive('form-wizard-two') }}">Form 82053</a></li>
                            <li class="{{ routeActive('form-wizard-three') }}"><a href="{{ route('form-wizard-three') }}" class="{{ routeActive('form-wizard-three') }}">Form 84490</a></li>
                            <li class="{{ routeActive('form-wizard-four') }}"><a href="{{ route('form-wizard-four') }}" class="{{ routeActive('form-wizard-four') }}">Form 84491</a></li>
                        </ul>
                    </li>
                    <li class="dropdown {{ routeActive('jsgrid-table') }}">
                        <a class="nav-link menu-title link-nav {{ routeActive('jsgrid-table') }}" href="{{ route('jsgrid-table') }}">
                            <i data-feather="file-text"></i><span>My Forms</span>
                        </a>
                    </li>

                    {{-- Support --}}
                    <li class="sidebar-main-title">
                        <div><h6>Support</h6></div>
                    </li>
                    <li class="{{ routeActive('support-ticket') }}">
                        <a class="nav-link menu-title link-nav {{ routeActive('support-ticket') }}" href="{{ route('support-ticket') }}">
                            <i data-feather="headphones"></i><span>Support Ticket</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('help.manual') ? 'active' : '' }}">
                        <a class="nav-link menu-title link-nav {{ request()->routeIs('help.manual') ? 'active' : '' }}" href="{{ route('help.manual') }}">
                            <i data-feather="book-open"></i><span>User Manual</span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="right-arrow" id="right-arrow"><i data-feather="arrow-right"></i></div>
        </div>
    </nav>
</header>
