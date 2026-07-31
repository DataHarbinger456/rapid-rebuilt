<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Rapid Rebuilt - Florida Rebuilt Title Services">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <link rel="icon" href="<?php echo e(asset('assets/images/favicon.png')); ?>" type="image/x-icon">
    <link rel="shortcut icon" href="<?php echo e(asset('assets/images/favicon.png')); ?>" type="image/x-icon">
    <title><?php echo $__env->yieldContent('title'); ?> - Rapid Rebuilt</title>

    <!-- Google font-->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <?php if ($__env->exists('layouts.customer.partials.css')) echo $__env->make('layouts.customer.partials.css', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</head>
<body>
    <!-- Loader -->
    <div class="loader-wrapper">
        <div class="theme-loader"></div>
    </div>

    <!-- Page Wrapper -->
    <div class="page-wrapper compact-wrapper" id="pageWrapper">
        <!-- Header -->
        <?php if ($__env->exists('layouts.customer.partials.header')) echo $__env->make('layouts.customer.partials.header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <!-- Page Body -->
        <div class="page-body-wrapper sidebar-icon">
            <!-- Sidebar -->
            <?php if ($__env->exists('layouts.customer.partials.sidebar')) echo $__env->make('layouts.customer.partials.sidebar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

            <!-- Main Content -->
            <div class="page-body">
                <?php echo $__env->yieldContent('content'); ?>
            </div>

            <!-- Footer -->
            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-6 footer-copyright">
                            <p class="mb-0">Copyright <?php echo e(date('Y')); ?> © Rapid Rebuilt LLC. All rights reserved.</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <p class="mb-0">
                                <a href="<?php echo e(route('customer.support') ?? '#'); ?>">Support</a> |
                                <a href="<?php echo e(route('customer.faq') ?? '#'); ?>">FAQ</a>
                            </p>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <?php if ($__env->exists('layouts.customer.partials.js')) echo $__env->make('layouts.customer.partials.js', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /Users/studiomac/gg-projects/rapid-rebuilt/resources/views/layouts/customer/master.blade.php ENDPATH**/ ?>