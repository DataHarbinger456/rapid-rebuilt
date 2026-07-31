

<?php $__env->startSection('title'); ?>My Cases <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('breadcrumb_title'); ?>
            <h3>My Cases</h3>
        <?php $__env->endSlot(); ?>
        <li class="breadcrumb-item"><a href="<?php echo e(route('customer.dashboard')); ?>">Dashboard</a></li>
        <li class="breadcrumb-item active">My Cases</li>
    <?php echo $__env->renderComponent(); ?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>All Cases</h5>
                        <a href="<?php echo e(route('customer.cases.create')); ?>" class="btn btn-primary">
                            <i data-feather="plus"></i> Start New Case
                        </a>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <form method="GET" class="row mb-4">
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="draft" <?php echo e(request('status') === 'draft' ? 'selected' : ''); ?>>Draft</option>
                                    <option value="submitted" <?php echo e(request('status') === 'submitted' ? 'selected' : ''); ?>>Submitted</option>
                                    <option value="pending_payment" <?php echo e(request('status') === 'pending_payment' ? 'selected' : ''); ?>>Pending Payment</option>
                                    <option value="paid" <?php echo e(request('status') === 'paid' ? 'selected' : ''); ?>>Paid</option>
                                    <option value="scheduled" <?php echo e(request('status') === 'scheduled' ? 'selected' : ''); ?>>Scheduled</option>
                                    <option value="completed" <?php echo e(request('status') === 'completed' ? 'selected' : ''); ?>>Completed</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" placeholder="Search by VIN or case number..."
                                       value="<?php echo e(request('search')); ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i data-feather="search"></i> Search
                                </button>
                            </div>
                        </form>

                        <!-- Cases List -->
                        <?php $__empty_1 = true; $__currentLoopData = $cases ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $case): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="card mb-3 border <?php echo e($case->hasRequiredAction ? 'border-warning' : ''); ?>">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-3">
                                            <h6 class="mb-1">Case #<?php echo e($case->case_number); ?></h6>
                                            <span class="badge badge-<?php echo e($case->status_color); ?>"><?php echo e($case->status_label); ?></span>
                                        </div>
                                        <div class="col-md-4">
                                            <?php if($case->vehicle): ?>
                                                <strong><?php echo e($case->vehicle->year); ?> <?php echo e($case->vehicle->make); ?> <?php echo e($case->vehicle->model); ?></strong>
                                                <p class="mb-0 text-muted">VIN: <?php echo e($case->vehicle->vin); ?></p>
                                            <?php else: ?>
                                                <span class="text-muted">Vehicle information not added</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-2 text-center">
                                            <small class="text-muted d-block">Progress</small>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-primary" style="width: <?php echo e($case->progress_percent ?? 0); ?>%"></div>
                                            </div>
                                            <small><?php echo e($case->progress_percent ?? 0); ?>%</small>
                                        </div>
                                        <div class="col-md-3 text-end">
                                            <?php if($case->hasRequiredAction): ?>
                                                <span class="badge badge-warning mb-2 d-block">Action Required</span>
                                            <?php endif; ?>
                                            <a href="<?php echo e(route('customer.cases.show', $case)); ?>" class="btn btn-outline-primary btn-sm">
                                                <i data-feather="eye"></i> View
                                            </a>
                                            <?php if($case->status === 'draft'): ?>
                                                <a href="<?php echo e(route('customer.cases.edit', $case)); ?>" class="btn btn-outline-secondary btn-sm">
                                                    <i data-feather="edit"></i> Continue
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if($case->hasRequiredAction): ?>
                                    <div class="card-footer bg-warning-light">
                                        <i data-feather="alert-circle" class="text-warning"></i>
                                        <strong><?php echo e($case->requiredAction['title'] ?? 'Action needed'); ?></strong>
                                        <a href="<?php echo e($case->requiredAction['url'] ?? '#'); ?>" class="btn btn-sm btn-warning float-end">
                                            <?php echo e($case->requiredAction['button'] ?? 'Take Action'); ?>

                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <div class="text-center py-5">
                                <i data-feather="briefcase" style="width: 64px; height: 64px;" class="text-muted mb-3"></i>
                                <h4>No Cases Found</h4>
                                <p class="text-muted">Start your first case to begin the rebuilt title process.</p>
                                <a href="<?php echo e(route('customer.cases.create')); ?>" class="btn btn-primary btn-lg">
                                    <i data-feather="plus-circle"></i> Start New Case
                                </a>
                            </div>
                        <?php endif; ?>

                        <!-- Pagination -->
                        <?php if(isset($cases) && $cases->hasPages()): ?>
                            <div class="d-flex justify-content-center mt-4">
                                <?php echo e($cases->withQueryString()->links()); ?>

                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.customer.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/studiomac/gg-projects/rapid-rebuilt/resources/views/customer/cases/index.blade.php ENDPATH**/ ?>