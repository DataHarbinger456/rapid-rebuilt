

<?php $__env->startSection('title'); ?>Case Details <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('breadcrumb_title'); ?>
            <h3>Case #<?php echo e($case->case_number); ?></h3>
        <?php $__env->endSlot(); ?>
        <li class="breadcrumb-item">Admin</li>
        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.cases.index')); ?>">Cases</a></li>
        <li class="breadcrumb-item active"><?php echo e($case->case_number); ?></li>
    <?php echo $__env->renderComponent(); ?>

    <div class="container-fluid">
        <?php if(session('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo e(session('success')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(session('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo e(session('error')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Main Content -->
            <div class="col-xl-8">
                <!-- Customer Info -->
                <div class="card">
                    <div class="card-header">
                        <h5>Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <?php if($case->user): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Name:</strong> <?php echo e($case->user->name); ?></p>
                                    <p><strong>Email:</strong> <?php echo e($case->user->email); ?></p>
                                    <p><strong>Phone:</strong> <?php echo e($case->user->phone ?? 'N/A'); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Address:</strong> <?php echo e($case->user->address_1 ?? 'N/A'); ?></p>
                                    <p><strong>City/State:</strong> <?php echo e($case->user->city ?? ''); ?>, <?php echo e($case->user->states ?? ''); ?> <?php echo e($case->user->zip ?? ''); ?></p>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Customer information not available</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Vehicle Info -->
                <div class="card">
                    <div class="card-header">
                        <h5>Vehicle Information</h5>
                    </div>
                    <div class="card-body">
                        <?php if($case->vehicle): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr><td class="text-muted">VIN:</td><td><strong class="font-monospace"><?php echo e($case->vehicle->vin); ?></strong></td></tr>
                                        <tr><td class="text-muted">Year:</td><td><?php echo e($case->vehicle->year); ?></td></tr>
                                        <tr><td class="text-muted">Make:</td><td><?php echo e($case->vehicle->make); ?></td></tr>
                                        <tr><td class="text-muted">Model:</td><td><?php echo e($case->vehicle->model); ?></td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr><td class="text-muted">Body Type:</td><td><?php echo e($case->vehicle->body_type ?? 'N/A'); ?></td></tr>
                                        <tr><td class="text-muted">Color:</td><td><?php echo e($case->vehicle->color ?? 'N/A'); ?></td></tr>
                                        <tr><td class="text-muted">Odometer:</td><td><?php echo e(number_format($case->vehicle->odometer_reading ?? 0)); ?> mi</td></tr>
                                        <tr><td class="text-muted">Title Brand:</td><td><?php echo e(ucfirst($case->vehicle->title_brand ?? 'N/A')); ?></td></tr>
                                    </table>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Vehicle information not available</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Documents -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Documents</h5>
                        <?php if(count($missing_documents) > 0): ?>
                            <span class="badge badge-warning"><?php echo e(count($missing_documents)); ?> missing</span>
                        <?php else: ?>
                            <span class="badge badge-success">All documents uploaded</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Document Type</th>
                                        <th>Status</th>
                                        <th>Uploaded</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__empty_1 = true; $__currentLoopData = $case->documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <tr>
                                            <td><?php echo e(ucfirst(str_replace('_', ' ', $doc->document_type))); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo e($doc->status === 'approved' ? 'success' :
                                                    ($doc->status === 'rejected' ? 'danger' : 'warning')); ?>">
                                                    <?php echo e(ucfirst($doc->status)); ?>

                                                </span>
                                            </td>
                                            <td><?php echo e($doc->created_at->format('M j, Y')); ?></td>
                                            <td>
                                                <a href="#" class="btn btn-sm btn-outline-primary">View</a>
                                                <?php if($doc->status === 'pending'): ?>
                                                    <button class="btn btn-sm btn-success">Approve</button>
                                                    <button class="btn btn-sm btn-danger">Reject</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No documents uploaded</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Components -->
                <div class="card">
                    <div class="card-header">
                        <h5>Major Components</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Part Type</th>
                                        <th>Condition</th>
                                        <th>Source</th>
                                        <th>Part #</th>
                                        <th>Verified</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__empty_1 = true; $__currentLoopData = $case->components; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $component): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <tr>
                                            <td><?php echo e(ucfirst(str_replace('_', ' ', $component->part_type))); ?></td>
                                            <td><span class="badge badge-secondary"><?php echo e(ucfirst($component->condition)); ?></span></td>
                                            <td><?php echo e($component->source ?? 'N/A'); ?></td>
                                            <td><code><?php echo e($component->part_number ?? '-'); ?></code></td>
                                            <td>
                                                <?php if($component->verified): ?>
                                                    <span class="text-success"><i data-feather="check-circle"></i></span>
                                                <?php else: ?>
                                                    <span class="text-muted"><i data-feather="circle"></i></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No components registered</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="card">
                    <div class="card-header">
                        <h5>Case Timeline</h5>
                    </div>
                    <div class="card-body">
                        <?php $__empty_1 = true; $__currentLoopData = $case->timeline->sortByDesc('created_at'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0 me-3">
                                    <div class="bg-light rounded-circle p-2">
                                        <i data-feather="<?php echo e($event->event === 'status_changed' ? 'refresh-cw' : 'activity'); ?>" style="width: 16px; height: 16px;"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-1"><strong><?php echo e($event->title); ?></strong></p>
                                    <p class="mb-1 text-muted"><?php echo e($event->description); ?></p>
                                    <small class="text-muted"><?php echo e($event->created_at->format('M j, Y g:i A')); ?></small>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <p class="text-muted text-center">No timeline events</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-xl-4">
                <!-- Status Card -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Case Status</h5>
                    </div>
                    <div class="card-body text-center">
                        <span class="badge badge-lg p-3" style="font-size: 1rem;">
                            <?php echo e(ucfirst(str_replace('_', ' ', $case->status))); ?>

                        </span>
                        <hr>
                        <form action="<?php echo e(route('admin.cases.updateStatus', $case)); ?>" method="POST" class="mb-3">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3">
                                <label class="form-label">Change Status</label>
                                <select name="status" class="form-select">
                                    <?php $__currentLoopData = $available_statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($status); ?>" <?php echo e($case->status === $status ? 'selected' : ''); ?>>
                                            <?php echo e(ucfirst(str_replace('_', ' ', $status))); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Update Status</button>
                        </form>
                    </div>
                </div>

                <!-- Assignment -->
                <div class="card">
                    <div class="card-header">
                        <h5>Inspector Assignment</h5>
                    </div>
                    <div class="card-body">
                        <?php if($case->assignedInspector): ?>
                            <p><strong>Assigned to:</strong> <?php echo e($case->assignedInspector->name); ?></p>
                        <?php else: ?>
                            <p class="text-muted">No inspector assigned</p>
                        <?php endif; ?>
                        <form action="<?php echo e(route('admin.cases.assign', $case)); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <select name="inspector_id" class="form-select mb-2" required>
                                <option value="">Select Inspector...</option>
                                <?php $__currentLoopData = \App\Models\User::inspectors()->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inspector): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($inspector->id); ?>" <?php echo e($case->assigned_inspector_id == $inspector->id ? 'selected' : ''); ?>>
                                        <?php echo e($inspector->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <button type="submit" class="btn btn-outline-primary w-100">Assign Inspector</button>
                        </form>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="card">
                    <div class="card-header">
                        <h5>Payment Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Amount:</span>
                            <strong>US$<?php echo e(number_format($case->total_amount ?? 0, 2, '.', ',')); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Status:</span>
                            <span class="badge badge-<?php echo e($case->is_paid ? 'success' : 'warning'); ?>">
                                <?php echo e($case->is_paid ? 'Paid' : 'Pending'); ?>

                            </span>
                        </div>
                        <?php if($case->paid_at): ?>
                            <div class="d-flex justify-content-between">
                                <span>Paid on:</span>
                                <span><?php echo e($case->paid_at->format('M j, Y')); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Inspection -->
                <?php if($case->inspection): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5>Inspection</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Result:</span>
                                <span class="badge badge-<?php echo e($case->inspection->result === 'passed' ? 'success' : ($case->inspection->result === 'failed' ? 'danger' : 'warning')); ?>">
                                    <?php echo e(ucfirst($case->inspection->result)); ?>

                                </span>
                            </div>
                            <?php if($case->inspection->completed_at): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Completed:</span>
                                    <span><?php echo e($case->inspection->completed_at->format('M j, Y')); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between">
                                <span>Photos:</span>
                                <span><?php echo e($case->inspection->photos->count()); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Add Note -->
                <div class="card">
                    <div class="card-header">
                        <h5>Add Note</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo e(route('admin.cases.addNote', $case)); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <textarea name="note" class="form-control mb-2" rows="3" placeholder="Add an internal note..." required></textarea>
                            <button type="submit" class="btn btn-outline-primary w-100">Add Note</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/studiomac/gg-projects/rapid-rebuilt/resources/views/admin/cases/show.blade.php ENDPATH**/ ?>