<?php $__env->startSection('title'); ?>Dashboard <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<style>
    .stat-card {
        border-radius: 15px;
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .stat-icon svg {
        width: 28px;
        height: 28px;
    }
    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .appointment-item {
        border-left: 4px solid #7366ff;
        padding-left: 15px;
        margin-bottom: 15px;
    }
    .quick-action-btn {
        padding: 15px;
        border-radius: 10px;
        text-align: center;
        transition: all 0.2s;
    }
    .quick-action-btn:hover {
        background: #f8f9fa;
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
    <?php $__env->slot('breadcrumb_title'); ?>
        <h3>Dashboard</h3>
    <?php $__env->endSlot(); ?>
    <li class="breadcrumb-item active">Overview</li>
<?php echo $__env->renderComponent(); ?>

<div class="container-fluid">
    <!-- Welcome Banner -->
    <div class="row">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="text-white mb-1">Welcome back, <?php echo e(auth()->user()->first_name); ?>!</h4>
                            <p class="mb-0 opacity-75">Here's what's happening with your cases today.</p>
                        </div>
                        <div class="d-none d-md-block">
                            <span class="badge bg-light text-primary fs-6"><?php echo e(now()->format('l, F j, Y')); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-light-primary text-primary">
                            <i data-feather="briefcase"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0"><?php echo e($stats['total_cases'] ?? 0); ?></h3>
                            <p class="text-muted mb-0">Total Cases</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="badge bg-light-success text-success"><?php echo e($stats['active_cases'] ?? 0); ?> active</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-light-warning text-warning">
                            <i data-feather="clock"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0"><?php echo e($stats['pending_review'] ?? 0); ?></h3>
                            <p class="text-muted mb-0">Pending Review</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="<?php echo e(route('admin.cases.index', ['status' => 'submitted'])); ?>" class="text-warning">View all <i data-feather="arrow-right" style="width: 14px;"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-light-info text-info">
                            <i data-feather="calendar"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0"><?php echo e($stats['scheduled_today'] ?? 0); ?></h3>
                            <p class="text-muted mb-0">Today's Appointments</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="<?php echo e(route('calendar-basic')); ?>" class="text-info">View calendar <i data-feather="arrow-right" style="width: 14px;"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-light-success text-success">
                            <i data-feather="dollar-sign"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0">US$<?php echo e(number_format($stats['monthly_revenue'] ?? 0, 2, '.', ',')); ?></h3>
                            <p class="text-muted mb-0">Monthly Revenue</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="badge bg-light-success text-success"><?php echo e($stats['completed_this_month'] ?? 0); ?> completed</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <a href="<?php echo e(route('admin.cases.index')); ?>" class="quick-action-btn d-block text-center text-decoration-none">
                                <div class="stat-icon bg-light-primary text-primary mx-auto mb-2">
                                    <i data-feather="list"></i>
                                </div>
                                <span class="text-dark">All Cases</span>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?php echo e(route('admin.settings.users')); ?>" class="quick-action-btn d-block text-center text-decoration-none">
                                <div class="stat-icon bg-light-success text-success mx-auto mb-2">
                                    <i data-feather="users"></i>
                                </div>
                                <span class="text-dark">Manage Users</span>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?php echo e(route('admin.reports.index')); ?>" class="quick-action-btn d-block text-center text-decoration-none">
                                <div class="stat-icon bg-light-warning text-warning mx-auto mb-2">
                                    <i data-feather="bar-chart-2"></i>
                                </div>
                                <span class="text-dark">Reports</span>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?php echo e(route('admin.settings.index')); ?>" class="quick-action-btn d-block text-center text-decoration-none">
                                <div class="stat-icon bg-light-secondary text-secondary mx-auto mb-2">
                                    <i data-feather="settings"></i>
                                </div>
                                <span class="text-dark">Settings</span>
                            </a>
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Recent Cases</h5>
                    <a href="<?php echo e(route('admin.cases.index')); ?>" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Case #</th>
                                    <th>Customer</th>
                                    <th>Vehicle</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $recentCases ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $case): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td><strong><?php echo e($case->case_number); ?></strong></td>
                                    <td><?php echo e($case->user->name ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if($case->vehicle): ?>
                                            <?php echo e($case->vehicle->year); ?> <?php echo e($case->vehicle->make); ?> <?php echo e($case->vehicle->model); ?>

                                        <?php else: ?>
                                            <span class="text-muted">No vehicle</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                            $statusColors = [
                                                'draft' => 'secondary',
                                                'submitted' => 'info',
                                                'pending_payment' => 'warning',
                                                'paid' => 'primary',
                                                'pending_documents' => 'warning',
                                                'documents_complete' => 'info',
                                                'pending_scheduling' => 'warning',
                                                'scheduled' => 'primary',
                                                'inspection_in_progress' => 'info',
                                                'passed' => 'success',
                                                'failed' => 'danger',
                                                'completed' => 'success',
                                                'cancelled' => 'danger',
                                            ];
                                            $color = $statusColors[$case->status] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo e($color); ?>"><?php echo e(ucwords(str_replace('_', ' ', $case->status))); ?></span>
                                    </td>
                                    <td><?php echo e($case->created_at->diffForHumans()); ?></td>
                                    <td>
                                        <a href="<?php echo e(route('admin.cases.show', $case)); ?>" class="btn btn-sm btn-light">
                                            <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No cases found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Appointments & Stats -->
        <div class="col-xl-4">
            <!-- Today's Appointments -->
            <div class="card">
                <div class="card-header">
                    <h5>Today's Appointments</h5>
                </div>
                <div class="card-body">
                    <?php $__empty_1 = true; $__currentLoopData = $todayAppointments ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $appointment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="appointment-item">
                        <div class="d-flex justify-content-between">
                            <strong><?php echo e($appointment->start->format('h:i A')); ?></strong>
                            <span class="badge bg-<?php echo e($appointment->status === 'confirmed' ? 'primary' : 'secondary'); ?>">
                                <?php echo e(ucfirst($appointment->status)); ?>

                            </span>
                        </div>
                        <p class="mb-1"><?php echo e($appointment->userCase->case_number ?? 'N/A'); ?></p>
                        <small class="text-muted">
                            <i data-feather="user" style="width: 12px; height: 12px;"></i>
                            <?php echo e($appointment->user->name ?? 'N/A'); ?>

                        </small>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="text-center py-4">
                        <i data-feather="calendar" style="width: 48px; height: 48px;" class="text-muted mb-2"></i>
                        <p class="text-muted mb-0">No appointments today</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cases by Status -->
            <div class="card">
                <div class="card-header">
                    <h5>Cases by Status</h5>
                </div>
                <div class="card-body">
                    <?php
                        $statusLabels = [
                            'draft' => ['label' => 'Draft', 'color' => 'secondary'],
                            'submitted' => ['label' => 'Submitted', 'color' => 'info'],
                            'pending_payment' => ['label' => 'Pending Payment', 'color' => 'warning'],
                            'paid' => ['label' => 'Paid', 'color' => 'primary'],
                            'scheduled' => ['label' => 'Scheduled', 'color' => 'primary'],
                            'completed' => ['label' => 'Completed', 'color' => 'success'],
                        ];
                        $total = array_sum($casesByStatus ?? []);
                    ?>
                    <?php $__empty_1 = true; $__currentLoopData = $casesByStatus ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status => $count): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $info = $statusLabels[$status] ?? ['label' => ucwords(str_replace('_', ' ', $status)), 'color' => 'secondary'];
                        $percentage = $total > 0 ? round(($count / $total) * 100) : 0;
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span><?php echo e($info['label']); ?></span>
                            <span class="text-muted"><?php echo e($count); ?></span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-<?php echo e($info['color']); ?>" style="width: <?php echo e($percentage); ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="text-muted text-center">No data available</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- User Stats -->
            <div class="card">
                <div class="card-header">
                    <h5>User Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-light-primary text-primary me-2" style="width: 40px; height: 40px;">
                                <i data-feather="users" style="width: 18px; height: 18px;"></i>
                            </div>
                            <span>Customers</span>
                        </div>
                        <strong><?php echo e($stats['total_customers'] ?? 0); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-light-success text-success me-2" style="width: 40px; height: 40px;">
                                <i data-feather="user-check" style="width: 18px; height: 18px;"></i>
                            </div>
                            <span>Inspectors</span>
                        </div>
                        <strong><?php echo e($stats['total_inspectors'] ?? 0); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script>
    // Initialize feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/studiomac/gg-projects/rapid-rebuilt/resources/views/admin/dashboard/default.blade.php ENDPATH**/ ?>