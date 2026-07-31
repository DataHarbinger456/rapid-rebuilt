

<?php $__env->startSection('title'); ?>Case #<?php echo e($case->case_number); ?> <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('breadcrumb_title'); ?>
            <h3>Case #<?php echo e($case->case_number); ?></h3>
        <?php $__env->endSlot(); ?>
        <li class="breadcrumb-item"><a href="<?php echo e(route('customer.dashboard')); ?>">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo e(route('customer.cases.index')); ?>">Cases</a></li>
        <li class="breadcrumb-item active"><?php echo e($case->case_number); ?></li>
    <?php echo $__env->renderComponent(); ?>

    <div class="container-fluid">
        <!-- Status Banner -->
        <?php if($case->hasRequiredAction): ?>
            <div class="alert alert-warning d-flex justify-content-between align-items-center mb-4">
                <div>
                    <i data-feather="alert-circle"></i>
                    <strong><?php echo e($case->requiredAction['title'] ?? 'Action Required'); ?></strong>
                    <span class="ms-2"><?php echo e($case->requiredAction['description'] ?? ''); ?></span>
                </div>
                <a href="<?php echo e($case->requiredAction['url'] ?? '#'); ?>" class="btn btn-warning">
                    <?php echo e($case->requiredAction['button'] ?? 'Take Action'); ?>

                </a>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Main Content -->
            <div class="col-xl-8">
                <!-- Progress Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Case Progress</h5>
                            <span class="badge badge-<?php echo e($case->status_color); ?> badge-lg"><?php echo e($case->status_label); ?></span>
                        </div>
                        <div class="progress mb-3" style="height: 25px;">
                            <div class="progress-bar progress-bar-striped bg-primary" role="progressbar"
                                 style="width: <?php echo e($case->progress_percent); ?>%;"
                                 aria-valuenow="<?php echo e($case->progress_percent); ?>" aria-valuemin="0" aria-valuemax="100">
                                <?php echo e($case->progress_percent); ?>%
                            </div>
                        </div>
                        <div class="d-flex justify-content-between text-muted small">
                            <span>Submitted</span>
                            <span>Documents</span>
                            <span>Scheduled</span>
                            <span>Inspection</span>
                            <span>Complete</span>
                        </div>
                    </div>
                </div>

                <!-- Vehicle Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i data-feather="truck"></i> Vehicle Information</h5>
                    </div>
                    <div class="card-body">
                        <?php if($case->vehicle): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="text-muted">VIN:</td>
                                            <td><strong><?php echo e($case->vehicle->vin); ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Year:</td>
                                            <td><?php echo e($case->vehicle->year); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Make:</td>
                                            <td><?php echo e($case->vehicle->make); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Model:</td>
                                            <td><?php echo e($case->vehicle->model); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="text-muted">Color:</td>
                                            <td><?php echo e(ucfirst($case->vehicle->color ?? '-')); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Body Style:</td>
                                            <td><?php echo e($case->vehicle->body_style ?? '-'); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Odometer:</td>
                                            <td><?php echo e(number_format($case->vehicle->odometer ?? 0)); ?> miles</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Title State:</td>
                                            <td><?php echo e($case->vehicle->title_state ?? '-'); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Vehicle information not yet added.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Components -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i data-feather="settings"></i> Major Components</h5>
                        <span class="badge badge-primary"><?php echo e($case->components->count()); ?> components</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Component</th>
                                    <th>Source</th>
                                    <th>Condition</th>
                                    <th>Verified</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $case->components; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $component): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo e(ucfirst(str_replace('_', ' ', $component->type))); ?></strong>
                                            <?php if($component->description): ?>
                                                <br><small class="text-muted"><?php echo e($component->description); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo e(ucfirst(str_replace('_', ' ', $component->source))); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo e($component->condition === 'good' ? 'success' : ($component->condition === 'damaged' ? 'danger' : 'secondary')); ?>">
                                                <?php echo e(ucfirst($component->condition ?? 'Pending')); ?>

                                            </span>
                                        </td>
                                        <td>
                                            <?php if($component->verified): ?>
                                                <i data-feather="check-circle" class="text-success"></i>
                                            <?php else: ?>
                                                <i data-feather="clock" class="text-muted"></i>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No components listed</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Documents -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i data-feather="file-text"></i> Documents</h5>
                        <?php if(in_array($case->status, ['draft', 'pending_documents'])): ?>
                            <a href="<?php echo e(route('customer.cases.documents', $case)); ?>" class="btn btn-sm btn-primary">
                                <i data-feather="upload"></i> Upload Documents
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Document</th>
                                    <th>Status</th>
                                    <th>Uploaded</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $case->documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td>
                                            <i data-feather="<?php echo e($document->icon ?? 'file'); ?>" class="text-<?php echo e($document->status_color); ?>"></i>
                                            <?php echo e($document->document_type_label); ?>

                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo e($document->status_color); ?>">
                                                <?php echo e(ucfirst($document->status)); ?>

                                            </span>
                                            <?php if($document->rejection_reason): ?>
                                                <br><small class="text-danger"><?php echo e($document->rejection_reason); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo e($document->created_at->format('M j, Y')); ?></td>
                                        <td>
                                            <a href="<?php echo e(route('api.documents.download', $document)); ?>" class="btn btn-sm btn-outline-primary">
                                                <i data-feather="download"></i>
                                            </a>
                                            <?php if($document->status === 'rejected'): ?>
                                                <a href="<?php echo e(route('customer.documents.reupload', $document)); ?>" class="btn btn-sm btn-outline-warning">
                                                    <i data-feather="upload"></i> Re-upload
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            No documents uploaded yet.
                                            <?php if(in_array($case->status, ['draft', 'pending_documents'])): ?>
                                                <br><a href="<?php echo e(route('customer.cases.documents', $case)); ?>">Upload required documents</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="card">
                    <div class="card-header">
                        <h5><i data-feather="clock"></i> Case Timeline</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline-wrapper">
                            <?php $__empty_1 = true; $__currentLoopData = $case->timeline->sortByDesc('created_at'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <div class="timeline-item mb-4">
                                    <div class="d-flex">
                                        <div class="timeline-icon bg-<?php echo e($event->type_color ?? 'primary'); ?> text-white rounded-circle p-2 me-3">
                                            <i data-feather="<?php echo e($event->icon ?? 'circle'); ?>"></i>
                                        </div>
                                        <div class="timeline-content flex-grow-1">
                                            <div class="d-flex justify-content-between">
                                                <h6 class="mb-1"><?php echo e($event->title); ?></h6>
                                                <small class="text-muted"><?php echo e($event->created_at->format('M j, Y g:i A')); ?></small>
                                            </div>
                                            <p class="text-muted mb-0"><?php echo e($event->description); ?></p>
                                            <?php if($event->metadata && isset($event->metadata['by'])): ?>
                                                <small class="text-muted">By: <?php echo e($event->metadata['by']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <p class="text-muted text-center">No activity yet</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-xl-4">
                <!-- Quick Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Case Information</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Case Number:</span>
                                <strong><?php echo e($case->case_number); ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Status:</span>
                                <span class="badge badge-<?php echo e($case->status_color); ?>"><?php echo e($case->status_label); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Created:</span>
                                <span><?php echo e($case->created_at->format('M j, Y')); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Last Updated:</span>
                                <span><?php echo e($case->updated_at->diffForHumans()); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Appointment -->
                <?php if($case->appointment): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i data-feather="calendar"></i> Scheduled Inspection</h5>
                        </div>
                        <div class="card-body text-center">
                            <h4><?php echo e($case->appointment->start->format('l')); ?></h4>
                            <h5><?php echo e($case->appointment->start->format('F j, Y')); ?></h5>
                            <h3 class="text-primary"><?php echo e($case->appointment->start->format('g:i A')); ?></h3>
                            <?php if($case->appointment->location): ?>
                                <p class="text-muted mt-3">
                                    <i data-feather="map-pin"></i> <?php echo e($case->appointment->location); ?>

                                </p>
                            <?php endif; ?>
                            <?php if($case->appointment->inspector): ?>
                                <p class="text-muted">
                                    <i data-feather="user"></i> Inspector: <?php echo e($case->appointment->inspector->name); ?>

                                </p>
                            <?php endif; ?>
                            <?php if($case->appointment->canReschedule()): ?>
                                <a href="<?php echo e(route('customer.appointments.reschedule', $case->appointment)); ?>" class="btn btn-outline-primary btn-sm">
                                    <i data-feather="clock"></i> Reschedule
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif(in_array($case->status, ['paid', 'documents_complete', 'pending_scheduling'])): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Schedule Inspection</h5>
                        </div>
                        <div class="card-body text-center">
                            <i data-feather="calendar" style="width: 48px; height: 48px;" class="text-primary mb-3"></i>
                            <p class="text-muted">Your case is ready for scheduling.</p>
                            <a href="<?php echo e(route('customer.cases.schedule', $case)); ?>" class="btn btn-primary">
                                <i data-feather="calendar"></i> Schedule Now
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Payment Status -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Payment</h5>
                    </div>
                    <div class="card-body">
                        <?php if($case->payment_status === 'paid'): ?>
                            <div class="text-center text-success">
                                <i data-feather="check-circle" style="width: 48px; height: 48px;"></i>
                                <h5 class="mt-2">Paid</h5>
                                <p class="text-muted">US$<?php echo e(number_format($case->total_amount, 2, '.', ',')); ?></p>
                            </div>
                        <?php elseif($case->status === 'pending_payment'): ?>
                            <div class="text-center">
                                <h4 class="text-primary">US$<?php echo e(number_format($case->total_amount, 2, '.', ',')); ?></h4>
                                <p class="text-muted">Payment required to proceed</p>
                                <a href="<?php echo e(route('customer.cases.pay', $case)); ?>" class="btn btn-success btn-lg w-100">
                                    <i data-feather="credit-card"></i> Pay Now
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center mb-0">
                                Estimated: US$<?php echo e(number_format($case->estimated_amount ?? 150, 2, '.', ',')); ?>

                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Generated Documents -->
                <?php if($case->generatedDocuments && $case->generatedDocuments->count() > 0): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i data-feather="file-text"></i> HSMV Documents</h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php $__currentLoopData = $case->generatedDocuments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?php echo e($doc->name); ?></span>
                                        <a href="<?php echo e(route('api.documents.download', $doc)); ?>" class="btn btn-sm btn-outline-primary">
                                            <i data-feather="download"></i>
                                        </a>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="card">
                    <div class="card-header">
                        <h5>Actions</h5>
                    </div>
                    <div class="card-body">
                        <?php if($case->status === 'draft'): ?>
                            <a href="<?php echo e(route('customer.cases.edit', $case)); ?>" class="btn btn-primary w-100 mb-2">
                                <i data-feather="edit"></i> Continue Editing
                            </a>
                            <form action="<?php echo e(route('customer.cases.destroy', $case)); ?>" method="POST"
                                  onsubmit="return confirm('Are you sure you want to delete this draft?')">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i data-feather="trash-2"></i> Delete Draft
                                </button>
                            </form>
                        <?php endif; ?>

                        <a href="<?php echo e(route('customer.messages.create', ['case_id' => $case->id])); ?>" class="btn btn-outline-primary w-100 mt-2">
                            <i data-feather="message-circle"></i> Contact Support
                        </a>

                        <button type="button" class="btn btn-outline-secondary w-100 mt-2" id="copy-tracking-link"
                                data-tracking-url="<?php echo e($case->tracking_url); ?>">
                            <i data-feather="link"></i> Share Tracking Link
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('css'); ?>
<style>
.timeline-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.timeline-icon svg {
    width: 18px;
    height: 18px;
}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.getElementById('copy-tracking-link')?.addEventListener('click', function () {
    var btn = this;
    navigator.clipboard.writeText(btn.dataset.trackingUrl).then(function () {
        var original = btn.innerHTML;
        btn.innerHTML = 'Link copied!';
        setTimeout(function () {
            btn.innerHTML = original;
            if (window.feather) feather.replace();
        }, 2000);
    });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.customer.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/studiomac/gg-projects/rapid-rebuilt/resources/views/customer/cases/show.blade.php ENDPATH**/ ?>