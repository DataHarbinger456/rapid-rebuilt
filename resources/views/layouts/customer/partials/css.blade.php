<!-- Font Awesome -->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/fontawesome.css') }}">
<!-- ICO Icon -->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/icofont.css') }}">
<!-- Themify Icons -->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/themify.css') }}">
<!-- Flag Icons -->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/flag-icon.css') }}">
<!-- Feather Icons -->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/feather-icon.css') }}">
<!-- Plugins CSS -->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/sweetalert2.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/dropzone.css') }}">
<!-- Bootstrap CSS -->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/bootstrap.css') }}">
<!-- App CSS -->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/style.css') }}">
<link id="color" rel="stylesheet" href="{{ asset('assets/css/color-1.css') }}" media="screen">
<!-- Responsive CSS -->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive.css') }}">

<!-- Custom sidebar active styles -->
<style>
/* Active submenu items */
.nav-submenu li.active > a,
.nav-submenu li > a.active {
    color: #24695c !important;
    font-weight: 600 !important;
}
.nav-submenu li.active > a::after,
.nav-submenu li > a.active::after {
    background-color: #24695c !important;
}

/* Active parent menu */
.nav-menu > li.active > a.menu-title {
    background-color: #24695c !important;
    color: #fff !important;
}
.nav-menu > li.active > a.menu-title svg {
    color: #fff !important;
}

/* Hover effect on submenu */
.nav-submenu li:hover > a {
    color: #24695c !important;
}
</style>

@stack('css')
