<div class="page-main-header">
    <div class="main-header-right row m-0">
        <div class="main-header-left">
            <div class="logo-wrapper">
                <a href="<?php echo e(route('customer.dashboard')); ?>">
                    <img class="img-fluid" src="<?php echo e(asset('assets/images/logo/logo.png')); ?>" alt="Rapid Rebuilt">
                </a>
            </div>
            <div class="dark-logo-wrapper">
                <a href="<?php echo e(route('customer.dashboard')); ?>">
                    <img class="img-fluid" src="<?php echo e(asset('assets/images/logo/dark-logo.png')); ?>" alt="Rapid Rebuilt">
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
                        <?php if(isset($unreadNotifications) && $unreadNotifications > 0): ?>
                            <span class="dot-animated"></span>
                        <?php endif; ?>
                    </div>
                    <ul class="notification-dropdown onhover-show-div">
                        <li>
                            <p class="f-w-700 mb-0">Notifications
                                <?php if(isset($unreadNotifications) && $unreadNotifications > 0): ?>
                                    <span class="pull-right badge badge-primary badge-pill"><?php echo e($unreadNotifications); ?></span>
                                <?php endif; ?>
                            </p>
                        </li>
                        <?php $__empty_1 = true; $__currentLoopData = $notifications ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <li class="noti-<?php echo e($notification->read_at ? '' : 'primary'); ?>">
                                <div class="media">
                                    <span class="notification-bg bg-light-<?php echo e($notification->type ?? 'primary'); ?>">
                                        <i data-feather="<?php echo e($notification->icon ?? 'bell'); ?>"></i>
                                    </span>
                                    <div class="media-body">
                                        <p><?php echo e($notification->message ?? ''); ?></p>
                                        <span><?php echo e($notification->created_at->diffForHumans()); ?></span>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <li class="txt-dark text-center py-3">No new notifications</li>
                        <?php endif; ?>
                        <li>
                            <a class="btn btn-primary" href="<?php echo e(route('customer.notifications') ?? '#'); ?>">View All</a>
                        </li>
                    </ul>
                </li>

                <!-- Messages -->
                <li class="onhover-dropdown">
                    <i data-feather="message-square"></i>
                    <?php if(isset($unreadMessages) && $unreadMessages > 0): ?>
                        <span class="dot-animated"></span>
                    <?php endif; ?>
                    <ul class="chat-dropdown onhover-show-div">
                        <li>
                            <p class="f-w-700 mb-0">Messages
                                <?php if(isset($unreadMessages) && $unreadMessages > 0): ?>
                                    <span class="pull-right badge badge-primary badge-pill"><?php echo e($unreadMessages); ?></span>
                                <?php endif; ?>
                            </p>
                        </li>
                        <?php $__empty_1 = true; $__currentLoopData = $messages ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <li>
                                <div class="media">
                                    <div class="media-body">
                                        <span><?php echo e($message->subject); ?></span>
                                        <p class="f-12 light-font"><?php echo e(Str::limit($message->content, 50)); ?></p>
                                    </div>
                                    <p class="f-12 font-<?php echo e($message->read_at ? 'secondary' : 'primary'); ?>">
                                        <?php echo e($message->created_at->diffForHumans()); ?>

                                    </p>
                                </div>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <li class="text-center py-3">No messages</li>
                        <?php endif; ?>
                        <li class="text-center">
                            <a class="btn btn-primary" href="<?php echo e(route('customer.messages') ?? '#'); ?>">View All</a>
                        </li>
                    </ul>
                </li>

                <!-- User Profile -->
                <li class="onhover-dropdown p-0">
                    <div class="media profile-media">
                        <img class="rounded-circle" src="<?php echo e(auth()->user()->avatar ?? asset('assets/images/dashboard/profile.jpg')); ?>" alt="">
                        <div class="media-body">
                            <span><?php echo e(auth()->user()->name ?? 'Customer'); ?></span>
                            <p class="mb-0 font-roboto">Customer <i class="middle fa fa-angle-down"></i></p>
                        </div>
                    </div>
                    <ul class="profile-dropdown onhover-show-div">
                        <li><a href="<?php echo e(route('customer.profile') ?? '#'); ?>"><i data-feather="user"></i><span>My Profile</span></a></li>
                        <li><a href="<?php echo e(route('customer.settings') ?? '#'); ?>"><i data-feather="settings"></i><span>Settings</span></a></li>
                        <li>
                            <a href="<?php echo e(route('logout')); ?>" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i data-feather="log-out"></i><span>Log out</span>
                            </a>
                            <form id="logout-form" action="<?php echo e(route('logout')); ?>" method="POST" style="display: none;">
                                <?php echo csrf_field(); ?>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
<?php /**PATH /Users/studiomac/gg-projects/rapid-rebuilt/resources/views/layouts/customer/partials/header.blade.php ENDPATH**/ ?>