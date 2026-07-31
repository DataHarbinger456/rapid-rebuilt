

<?php $__env->startSection('title'); ?>Dashboard <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('breadcrumb_title'); ?>
            <h3>Welcome, <?php echo e(auth()->user()->name ?? 'Customer'); ?></h3>
        <?php $__env->endSlot(); ?>
        <li class="breadcrumb-item active">Dashboard</li>
    <?php echo $__env->renderComponent(); ?>

    <div class="container-fluid">
        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-white">
                                <h4 class="mb-2">Ready to get your rebuilt title?</h4>
                                <p class="mb-0">Start a new case and we'll guide you through the process.</p>
                            </div>
                            <a href="<?php echo e(route('customer.cases.create')); ?>" class="btn btn-light btn-lg">
                                <i data-feather="plus-circle"></i> Start New Case
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card o-hidden">
                    <div class="card-body">
                        <div class="media static-widget">
                            <div class="media-body">
                                <h6 class="font-roboto">Active Cases</h6>
                                <h4 class="mb-0 counter"><?php echo e($activeCases ?? 0); ?></h4>
                            </div>
                            <div class="align-self-center text-center">
                                <i data-feather="briefcase" class="font-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card o-hidden">
                    <div class="card-body">
                        <div class="media static-widget">
                            <div class="media-body">
                                <h6 class="font-roboto">Pending Documents</h6>
                                <h4 class="mb-0 counter"><?php echo e($pendingDocuments ?? 0); ?></h4>
                            </div>
                            <div class="align-self-center text-center">
                                <i data-feather="file-text" class="font-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card o-hidden">
                    <div class="card-body">
                        <div class="media static-widget">
                            <div class="media-body">
                                <h6 class="font-roboto">Upcoming Appointments</h6>
                                <h4 class="mb-0 counter"><?php echo e($upcomingAppointments ?? 0); ?></h4>
                            </div>
                            <div class="align-self-center text-center">
                                <i data-feather="calendar" class="font-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card o-hidden">
                    <div class="card-body">
                        <div class="media static-widget">
                            <div class="media-body">
                                <h6 class="font-roboto">Completed Cases</h6>
                                <h4 class="mb-0 counter"><?php echo e($completedCases ?? 0); ?></h4>
                            </div>
                            <div class="align-self-center text-center">
                                <i data-feather="check-circle" class="font-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Cases -->
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h5>My Recent Cases</h5>
                        <a href="<?php echo e(route('customer.cases.index')); ?>" class="btn btn-sm btn-outline-primary pull-right">
                            View All
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Case #</th>
                                        <th>Vehicle</th>
                                        <th>Status</th>
                                        <th>Last Updated</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__empty_1 = true; $__currentLoopData = $recentCases ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $case): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <tr>
                                            <td><strong><?php echo e($case->case_number); ?></strong></td>
                                            <td>
                                                <?php if($case->vehicle): ?>
                                                    <?php echo e($case->vehicle->year); ?> <?php echo e($case->vehicle->make); ?> <?php echo e($case->vehicle->model); ?>

                                                <?php else: ?>
                                                    <span class="text-muted">Not added</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo e($case->status_color); ?>">
                                                    <?php echo e($case->status_label); ?>

                                                </span>
                                            </td>
                                            <td><?php echo e($case->updated_at->diffForHumans()); ?></td>
                                            <td>
                                                <a href="<?php echo e(route('customer.cases.show', $case)); ?>" class="btn btn-sm btn-outline-primary">
                                                    <i data-feather="eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <p class="text-muted mb-2">You don't have any cases yet.</p>
                                                <a href="<?php echo e(route('customer.cases.create')); ?>" class="btn btn-primary">
                                                    Start Your First Case
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Required Actions -->
                <?php if(isset($requiredActions) && count($requiredActions) > 0): ?>
                    <div class="card">
                        <div class="card-header bg-warning">
                            <h5 class="text-white"><i data-feather="alert-circle"></i> Action Required</h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php $__currentLoopData = $requiredActions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo e($action['title']); ?></strong>
                                            <p class="mb-0 text-muted"><?php echo e($action['description']); ?></p>
                                        </div>
                                        <a href="<?php echo e($action['url']); ?>" class="btn btn-sm btn-warning">
                                            <?php echo e($action['button_text']); ?>

                                        </a>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-xl-4">
                <!-- Next Appointment -->
                <?php if(isset($nextAppointment)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5>Next Appointment</h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i data-feather="calendar" style="width: 48px; height: 48px;" class="text-primary"></i>
                            </div>
                            <h4><?php echo e($nextAppointment->start->format('l, F j, Y')); ?></h4>
                            <h5 class="text-primary"><?php echo e($nextAppointment->start->format('g:i A')); ?></h5>
                            <p class="text-muted mb-3">
                                <?php echo e($nextAppointment->location ?? 'Location TBD'); ?>

                            </p>
                            <a href="<?php echo e(route('customer.appointments.show', $nextAppointment)); ?>" class="btn btn-outline-primary">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Recent Notifications -->
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Updates</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php $__empty_1 = true; $__currentLoopData = $recentNotifications ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <li class="list-group-item">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0 me-3">
                                            <span class="badge badge-light-<?php echo e($notification->type ?? 'primary'); ?> p-2">
                                                <i data-feather="<?php echo e($notification->icon ?? 'bell'); ?>"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="mb-1"><?php echo e($notification->message); ?></p>
                                            <small class="text-muted"><?php echo e($notification->created_at->diffForHumans()); ?></small>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <li class="list-group-item text-center text-muted py-4">
                                    No recent updates
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- Help Card -->
                <div class="card">
                    <div class="card-body text-center">
                        <i data-feather="help-circle" style="width: 48px; height: 48px;" class="text-info mb-3"></i>
                        <h5>Need Help?</h5>
                        <p class="text-muted">Our team is ready to assist you with your rebuilt title process.</p>
                        <a href="<?php echo e(route('customer.support') ?? '#'); ?>" class="btn btn-info">
                            Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.customer.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/studiomac/gg-projects/rapid-rebuilt/resources/views/customer/dashboard.blade.php ENDPATH**/ ?>