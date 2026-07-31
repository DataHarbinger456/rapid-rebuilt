<!-- jQuery -->
<script src="<?php echo e(asset('assets/js/jquery-3.5.1.min.js')); ?>"></script>
<!-- Bootstrap JS -->
<script src="<?php echo e(asset('assets/js/bootstrap/popper.min.js')); ?>"></script>
<script src="<?php echo e(asset('assets/js/bootstrap/bootstrap.min.js')); ?>"></script>
<!-- Feather Icons -->
<script src="<?php echo e(asset('assets/js/icons/feather-icon/feather.min.js')); ?>"></script>
<script src="<?php echo e(asset('assets/js/icons/feather-icon/feather-icon.js')); ?>"></script>
<!-- Sidebar JS -->
<script src="<?php echo e(asset('assets/js/sidebar-menu.js')); ?>"></script>
<script src="<?php echo e(asset('assets/js/config.js')); ?>"></script>
<!-- Plugins JS -->
<script src="<?php echo e(asset('assets/js/sweet-alert/sweetalert.min.js')); ?>"></script>
<script src="<?php echo e(asset('assets/js/dropzone/dropzone.js')); ?>"></script>
<!-- Theme JS -->
<script src="<?php echo e(asset('assets/js/script.js')); ?>"></script>

<script>
// CSRF Token for AJAX
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Flash messages
<?php if(session('success')): ?>
    swal("Success!", "<?php echo e(session('success')); ?>", "success");
<?php endif; ?>

<?php if(session('error')): ?>
    swal("Error!", "<?php echo e(session('error')); ?>", "error");
<?php endif; ?>
</script>
<?php /**PATH /Users/studiomac/gg-projects/rapid-rebuilt/resources/views/layouts/customer/partials/js.blade.php ENDPATH**/ ?>