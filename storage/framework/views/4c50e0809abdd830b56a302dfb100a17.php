<header class="main-nav">
    <div class="sidebar-user text-center">
        <img class="img-90 rounded-circle" src="<?php echo e(asset('assets/images/dashboard/1.png')); ?>" alt="" />
        <div class="badge-bottom">
            <span class="badge badge-<?php echo e(Auth::user()->isAdmin() ? 'danger' : (Auth::user()->isStaff() ? 'warning' : 'primary')); ?>">
                <?php echo e(ucfirst(Auth::user()->user_type)); ?>

            </span>
        </div>
        <a href="<?php echo e(route('user-profile')); ?>">
            <h6 class="mt-3 f-14 f-w-600"><?php echo e(Auth::user()->user_type === 'dealer' ? Auth::user()->dealer_name : Auth::user()->full_name); ?></h6>
        </a>
        <p class="mb-0 font-roboto"><?php echo e(Auth::user()->email); ?></p>
    </div>
    <nav>
        <div class="main-navbar">
            <div class="left-arrow" id="left-arrow"><i data-feather="arrow-left"></i></div>
            <div id="mainnav">
                <ul class="nav-menu custom-scrollbar">
                    <li class="back-btn">
                        <div class="mobile-back text-end"><span>Back</span><i class="fa fa-angle-right ps-2" aria-hidden="true"></i></div>
                    </li>

                    
                    <?php if(Auth::user()->isStaff()): ?>
                        <li class="sidebar-main-title">
                            <div><h6>Administration</h6></div>
                        </li>
                        <li class="<?php echo e(request()->routeIs('dashboard') || request()->routeIs('admin.dashboard*') ? 'active' : ''); ?>">
                            <a class="nav-link menu-title link-nav <?php echo e(request()->routeIs('dashboard') || request()->routeIs('admin.dashboard*') ? 'active' : ''); ?>" href="<?php echo e(route('dashboard')); ?>">
                                <i data-feather="home"></i><span>Dashboard</span>
                            </a>
                        </li>
                        <li class="<?php echo e(request()->routeIs('admin.cases*') ? 'active' : ''); ?>">
                            <a class="nav-link menu-title link-nav <?php echo e(request()->routeIs('admin.cases*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.cases.index')); ?>">
                                <i data-feather="briefcase"></i><span>Case Management</span>
                            </a>
                        </li>
                        <li class="<?php echo e(request()->routeIs('admin.reports*') ? 'active' : ''); ?>">
                            <a class="nav-link menu-title link-nav <?php echo e(request()->routeIs('admin.reports*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.reports.index')); ?>">
                                <i data-feather="bar-chart-2"></i><span>Reports</span>
                            </a>
                        </li>
                        <li class="dropdown <?php echo e(routeActive('calendar-basic')); ?>">
                            <a class="nav-link menu-title link-nav <?php echo e(routeActive('calendar-basic')); ?>" href="<?php echo e(route('calendar-basic')); ?>">
                                <i data-feather="calendar"></i><span>Calendar</span>
                            </a>
                        </li>

                        <?php if(Auth::user()->isAdmin()): ?>
                            <li class="sidebar-main-title">
                                <div><h6>Settings</h6></div>
                            </li>
                            <li class="dropdown <?php echo e(request()->routeIs('admin.settings*') ? 'active' : ''); ?>">
                                <a class="nav-link menu-title <?php echo e(request()->routeIs('admin.settings*') ? 'active' : ''); ?>" href="javascript:void(0)">
                                    <i data-feather="settings"></i><span>Settings</span>
                                </a>
                                <ul class="nav-submenu menu-content" style="display: <?php echo e(request()->routeIs('admin.settings*') ? 'block' : 'none'); ?>;">
                                    <li class="<?php echo e(request()->routeIs('admin.settings.index') ? 'active' : ''); ?>"><a href="<?php echo e(route('admin.settings.index')); ?>" class="<?php echo e(request()->routeIs('admin.settings.index') ? 'active' : ''); ?>">General</a></li>
                                    <li class="<?php echo e(request()->routeIs('admin.settings.users*') ? 'active' : ''); ?>"><a href="<?php echo e(route('admin.settings.users')); ?>" class="<?php echo e(request()->routeIs('admin.settings.users*') ? 'active' : ''); ?>">User Management</a></li>
                                    <li class="<?php echo e(request()->routeIs('admin.settings.pricing*') ? 'active' : ''); ?>"><a href="<?php echo e(route('admin.settings.pricing')); ?>" class="<?php echo e(request()->routeIs('admin.settings.pricing*') ? 'active' : ''); ?>">Pricing</a></li>
                                    <li class="<?php echo e(request()->routeIs('admin.settings.notifications*') ? 'active' : ''); ?>"><a href="<?php echo e(route('admin.settings.notifications')); ?>" class="<?php echo e(request()->routeIs('admin.settings.notifications*') ? 'active' : ''); ?>">Notifications</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>

                    
                    <li class="sidebar-main-title">
                        <div><h6>General</h6></div>
                    </li>
                    <?php if(!Auth::user()->isStaff()): ?>
                        <li class="<?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>">
                            <a class="nav-link menu-title link-nav <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>" href="<?php echo e(route('dashboard')); ?>">
                                <i data-feather="home"></i><span>Dashboard</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="dropdown <?php echo e(prefixActive('/form-layout')); ?>">
                        <a class="nav-link menu-title <?php echo e(prefixActive('/form-layout')); ?>" href="javascript:void(0)">
                            <i data-feather="layout"></i><span>DMV Forms</span>
                        </a>
                        <ul class="nav-submenu menu-content" style="display: <?php echo e(prefixBlock('/form-layout')); ?>;">
                            <li class="<?php echo e(routeActive('default-form')); ?>"><a href="<?php echo e(route('default-form')); ?>" class="<?php echo e(routeActive('default-form')); ?>">Form 82040</a></li>
                            <li class="<?php echo e(routeActive('form-wizard')); ?>"><a href="<?php echo e(route('form-wizard')); ?>" class="<?php echo e(routeActive('form-wizard')); ?>">Form 82050</a></li>
                            <li class="<?php echo e(routeActive('form-wizard-two')); ?>"><a href="<?php echo e(route('form-wizard-two')); ?>" class="<?php echo e(routeActive('form-wizard-two')); ?>">Form 82053</a></li>
                            <li class="<?php echo e(routeActive('form-wizard-three')); ?>"><a href="<?php echo e(route('form-wizard-three')); ?>" class="<?php echo e(routeActive('form-wizard-three')); ?>">Form 84490</a></li>
                            <li class="<?php echo e(routeActive('form-wizard-four')); ?>"><a href="<?php echo e(route('form-wizard-four')); ?>" class="<?php echo e(routeActive('form-wizard-four')); ?>">Form 84491</a></li>
                        </ul>
                    </li>
                    <li class="dropdown <?php echo e(routeActive('jsgrid-table')); ?>">
                        <a class="nav-link menu-title link-nav <?php echo e(routeActive('jsgrid-table')); ?>" href="<?php echo e(route('jsgrid-table')); ?>">
                            <i data-feather="file-text"></i><span>My Forms</span>
                        </a>
                    </li>

                    
                    <li class="sidebar-main-title">
                        <div><h6>Support</h6></div>
                    </li>
                    <li class="<?php echo e(routeActive('support-ticket')); ?>">
                        <a class="nav-link menu-title link-nav <?php echo e(routeActive('support-ticket')); ?>" href="<?php echo e(route('support-ticket')); ?>">
                            <i data-feather="headphones"></i><span>Support Ticket</span>
                        </a>
                    </li>
                    <li class="<?php echo e(request()->routeIs('help.manual') ? 'active' : ''); ?>">
                        <a class="nav-link menu-title link-nav <?php echo e(request()->routeIs('help.manual') ? 'active' : ''); ?>" href="<?php echo e(route('help.manual')); ?>">
                            <i data-feather="book-open"></i><span>User Manual</span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="right-arrow" id="right-arrow"><i data-feather="arrow-right"></i></div>
        </div>
    </nav>
</header>
<?php /**PATH /Users/studiomac/gg-projects/rapid-rebuilt/resources/views/layouts/admin/partials/sidebar.blade.php ENDPATH**/ ?>