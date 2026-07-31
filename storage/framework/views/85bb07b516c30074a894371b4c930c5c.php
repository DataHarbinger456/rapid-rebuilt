<!DOCTYPE html>
<html lang="en" <?php if(Route::current()->getName() == 'layout-rtl'): ?> dir="rtl" <?php endif; ?>
    <?php switch(Route::current()->getName()):
      case ('layout-rtl'): ?>
          dir="rtl"
          <?php break; ?>
  <?php endswitch; ?>>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="description"
        content="Rapid Rebuilt LLC, DMV Rebuilt Inspection Agency in Tampa, Florida">
    <meta name="keywords"
        content="Rapid Rebuilt LLC, rebuilt, title, vehicles, inspections, DMV">
    <meta name="author" content="pixelstrap">
    <link rel="icon" href="<?php echo e(asset('assets/images/favicon.png')); ?>" type="image/x-icon">
    <link rel="shortcut icon" href="<?php echo e(asset('assets/images/favicon.png')); ?>" type="image/x-icon">
    <title><?php echo $__env->yieldContent('title'); ?></title>
    <!-- Google font-->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&amp;display=swap"
        rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&amp;display=swap"
        rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Rubik:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400;1,500;1,600;1,700;1,800;1,900&amp;display=swap"
        rel="stylesheet">
    <!-- Font Awesome-->
    <?php if ($__env->exists('layouts.admin.partials.css')) echo $__env->make('layouts.admin.partials.css', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</head>

<?php switch(Route::current()->getName()):
    case ('boxed-layout'): ?>

        <body class="box-layout">
        <?php break; ?>

        <?php case ('layout-rtl'): ?>

            <body class="rtl">
            <?php break; ?>

            <?php case ('layout-dark'): ?>

                <body class="dark-only">
                <?php break; ?>

                <?php default: ?>

                    <body>
                <?php endswitch; ?>
                <!-- Loader starts-->
                <div class="loader-wrapper">
                    <div class="theme-loader"></div>
                </div>
                <!-- Loader ends-->
                <!-- page-wrapper Start-->
                <div class="page-wrapper compact-sidebar" id="pageWrapper">
                    <!-- Page Header Start-->
                    <?php if ($__env->exists('layouts.admin.partials.header')) echo $__env->make('layouts.admin.partials.header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    <!-- Page Header Ends -->
                    <!-- Page Body Start-->
                    <div class="page-body-wrapper sidebar-icon">
                        <!-- Page Sidebar Start-->
                        <?php if ($__env->exists('layouts.admin.partials.sidebar')) echo $__env->make('layouts.admin.partials.sidebar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                        <!-- Page Sidebar Ends-->
                        <div class="page-body">
                            <!-- Container-fluid starts-->
                            <?php echo $__env->yieldContent('content'); ?>
                            <!-- Container-fluid Ends-->
                        </div>
                        <!-- footer start-->
                        <?php switch(Route::current()->getName()):
                            case ('footer-dark'): ?>
                                <footer class="footer footer-dark">
                                <?php break; ?>

                                <?php case ('footer-fixed'): ?>
                                    <footer class="footer footer-fix">
                                    <?php break; ?>

                                    <?php default: ?>
                                        <footer>
                                    <?php endswitch; ?>
                                    <div class="container-fluid">
                                        <div class="row">
                                            <div class="col-md-6 footer-copyright">
                                                <p class="mb-0">Copyright
                                                    <?php echo e(date('Y')); ?>-<?php echo e(date('y', strtotime('+1 year'))); ?> © Rapid Rebuilt LLC
                                                    All rights reserved.</p>
                                            </div>
                                         
                                        </div>
                                    </div>
                                </footer>
                    </div>
                </div>
                <!-- latest jquery-->
                <?php if ($__env->exists('layouts.admin.partials.js')) echo $__env->make('layouts.admin.partials.js', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </body>

</html>
<?php /**PATH /Users/studiomac/gg-projects/rapid-rebuilt/resources/views/layouts/admin/master.blade.php ENDPATH**/ ?>