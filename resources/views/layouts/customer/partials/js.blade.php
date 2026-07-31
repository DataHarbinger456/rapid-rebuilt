<!-- jQuery -->
<script src="{{ asset('assets/js/jquery-3.5.1.min.js') }}"></script>
<!-- Bootstrap JS -->
<script src="{{ asset('assets/js/bootstrap/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap/bootstrap.min.js') }}"></script>
<!-- Feather Icons -->
<script src="{{ asset('assets/js/icons/feather-icon/feather.min.js') }}"></script>
<script src="{{ asset('assets/js/icons/feather-icon/feather-icon.js') }}"></script>
<!-- Sidebar JS -->
<script src="{{ asset('assets/js/sidebar-menu.js') }}"></script>
<script src="{{ asset('assets/js/config.js') }}"></script>
<!-- Plugins JS -->
<script src="{{ asset('assets/js/sweet-alert/sweetalert.min.js') }}"></script>
<script src="{{ asset('assets/js/dropzone/dropzone.js') }}"></script>
<!-- Theme JS -->
<script src="{{ asset('assets/js/script.js') }}"></script>

<script>
// CSRF Token for AJAX
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Flash messages
@if(session('success'))
    swal("Success!", "{{ session('success') }}", "success");
@endif

@if(session('error'))
    swal("Error!", "{{ session('error') }}", "error");
@endif

{{-- Validation failures redirect back, so surface them instead of failing silently. --}}
@if(isset($errors) && $errors->any())
    swal("Please check your entries", @json(implode("\n", $errors->all())), "warning");
@endif
</script>
