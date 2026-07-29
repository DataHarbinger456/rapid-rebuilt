<header class="main-nav">
    <div class="sidebar-user text-center">
        <img class="img-90 rounded-circle" src="{{ auth()->user()->avatar ?? asset('assets/images/dashboard/profile.jpg') }}" alt="">
        <a href="{{ route('customer.profile') ?? '#' }}">
            <h6 class="mt-3 f-14 f-w-600">{{ auth()->user()->name ?? 'Customer' }}</h6>
        </a>
        <p class="mb-0 font-roboto">{{ auth()->user()->email ?? '' }}</p>
    </div>
    <nav>
        <div class="main-navbar">
            <div class="left-arrow" id="left-arrow"><i data-feather="arrow-left"></i></div>
            <div id="mainnav">
                <ul class="nav-menu custom-scrollbar">
                    <li class="back-btn">
                        <div class="mobile-back text-end">
                            <span>Back</span>
                            <i class="fa fa-angle-right ps-2" aria-hidden="true"></i>
                        </div>
                    </li>

                    <!-- Dashboard -->
                    <li class="sidebar-main-title">
                        <div><h6>Main Menu</h6></div>
                    </li>
                    <li class="{{ request()->routeIs('customer.dashboard') ? 'active' : '' }}">
                        <a class="nav-link menu-title link-nav {{ request()->routeIs('customer.dashboard') ? 'active' : '' }}"
                           href="{{ route('customer.dashboard') }}">
                            <i data-feather="home"></i><span>Dashboard</span>
                        </a>
                    </li>

                    <!-- My Cases -->
                    <li class="sidebar-main-title">
                        <div><h6>My Cases</h6></div>
                    </li>
                    <li class="dropdown {{ request()->is('customer/cases*') ? 'active' : '' }}">
                        <a class="nav-link menu-title {{ request()->is('customer/cases*') ? 'active' : '' }}" href="javascript:void(0)">
                            <i data-feather="briefcase"></i><span>Cases</span>
                        </a>
                        <ul class="nav-submenu menu-content" style="display: {{ request()->is('customer/cases*') ? 'block' : 'none' }};">
                            <li class="{{ request()->routeIs('customer.cases.index') ? 'active' : '' }}">
                                <a href="{{ route('customer.cases.index') }}" class="{{ request()->routeIs('customer.cases.index') ? 'active' : '' }}">
                                    My Cases
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('customer.cases.create') ? 'active' : '' }}">
                                <a href="{{ route('customer.cases.create') }}" class="{{ request()->routeIs('customer.cases.create') ? 'active' : '' }}">
                                    Start New Case
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Documents -->
                    <li class="{{ request()->routeIs('customer.documents.*') ? 'active' : '' }}">
                        <a class="nav-link menu-title link-nav {{ request()->routeIs('customer.documents.*') ? 'active' : '' }}"
                           href="{{ route('customer.documents.index') }}">
                            <i data-feather="file-text"></i><span>Documents</span>
                        </a>
                    </li>

                    <!-- Appointments -->
                    <li class="{{ request()->routeIs('customer.appointments.*') ? 'active' : '' }}">
                        <a class="nav-link menu-title link-nav {{ request()->routeIs('customer.appointments.*') ? 'active' : '' }}"
                           href="{{ route('customer.appointments.index') }}">
                            <i data-feather="calendar"></i><span>Appointments</span>
                        </a>
                    </li>

                    <!-- Payments -->
                    <li class="{{ request()->routeIs('customer.payments.*') ? 'active' : '' }}">
                        <a class="nav-link menu-title link-nav {{ request()->routeIs('customer.payments.*') ? 'active' : '' }}"
                           href="{{ route('customer.payments.index') ?? '#' }}">
                            <i data-feather="credit-card"></i><span>Payments</span>
                        </a>
                    </li>

                    <!-- Support -->
                    <li class="sidebar-main-title">
                        <div><h6>Support</h6></div>
                    </li>
                    <li class="{{ request()->routeIs('customer.messages.*') ? 'active' : '' }}">
                        <a class="nav-link menu-title link-nav {{ request()->routeIs('customer.messages.*') ? 'active' : '' }}"
                           href="{{ route('customer.messages') ?? '#' }}">
                            <i data-feather="message-circle"></i><span>Messages</span>
                            @if(isset($unreadMessages) && $unreadMessages > 0)
                                <span class="badge badge-primary pull-right">{{ $unreadMessages }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('customer.faq') ? 'active' : '' }}">
                        <a class="nav-link menu-title link-nav {{ request()->routeIs('customer.faq') ? 'active' : '' }}" href="{{ route('customer.faq') ?? '#' }}">
                            <i data-feather="help-circle"></i><span>FAQ</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('help.manual') ? 'active' : '' }}">
                        <a class="nav-link menu-title link-nav {{ request()->routeIs('help.manual') ? 'active' : '' }}"
                           href="{{ route('help.manual') }}">
                            <i data-feather="book-open"></i><span>User Manual</span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="right-arrow" id="right-arrow"><i data-feather="arrow-right"></i></div>
        </div>
    </nav>
</header>
