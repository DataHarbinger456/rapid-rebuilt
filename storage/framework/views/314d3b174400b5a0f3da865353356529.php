<?php $__env->startSection('title'); ?>Documents — Case #<?php echo e($case->case_number); ?> <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('breadcrumb_title'); ?>
            <h3>Case Documents</h3>
        <?php $__env->endSlot(); ?>
        <li class="breadcrumb-item"><a href="<?php echo e(route('customer.dashboard')); ?>">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo e(route('customer.cases.index')); ?>">Cases</a></li>
        <li class="breadcrumb-item"><a href="<?php echo e(route('customer.cases.show', $case)); ?>"><?php echo e($case->case_number); ?></a></li>
        <li class="breadcrumb-item active">Documents</li>
    <?php echo $__env->renderComponent(); ?>

    <div class="container-fluid">
        <?php if(session('success')): ?>
            <div class="alert alert-success"><?php echo e(session('success')); ?></div>
        <?php endif; ?>
        <?php if(session('error')): ?>
            <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
        <?php endif; ?>
        <?php if($errors->any()): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-7">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Required Documents</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php $__currentLoopData = \App\Services\DocumentService::REQUIRED_DOCUMENTS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type => $info): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $uploaded = $case->documents->where('document_type', $type)->sortByDesc('created_at')->first();
                                ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo e($info['label']); ?></strong>
                                        <?php if($info['required']): ?><span class="text-danger">*</span><?php endif; ?>
                                        <br><small class="text-muted"><?php echo e($info['description']); ?></small>
                                        <?php if($uploaded && $uploaded->status === 'rejected' && $uploaded->rejection_reason): ?>
                                            <br><small class="text-danger"><?php echo e($uploaded->rejection_reason); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <?php if($uploaded): ?>
                                        <span class="badge bg-<?php echo e(['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'replaced' => 'secondary'][$uploaded->status] ?? 'secondary'); ?>">
                                            <?php echo e(ucfirst($uploaded->status)); ?>

                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark">Missing</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h5>Upload Document</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo e(route('customer.documents.upload', $case)); ?>" method="POST" enctype="multipart/form-data">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3">
                                <label class="form-label">Document Type</label>
                                <select name="document_type" class="form-select" required>
                                    <?php $__currentLoopData = \App\Services\DocumentService::REQUIRED_DOCUMENTS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type => $info): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($type); ?>"><?php echo e($info['label']); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">File (PDF, JPG, PNG — max 10 MB)</label>
                                <input type="file" name="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i data-feather="upload"></i> Upload
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.customer.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/studiomac/gg-projects/rapid-rebuilt/resources/views/customer/cases/documents.blade.php ENDPATH**/ ?>