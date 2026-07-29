<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Rapid Rebuilt - Florida Rebuilt Title Services">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('assets/images/favicon.png') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.png') }}" type="image/x-icon">
    <title>@yield('title') - Rapid Rebuilt</title>

    <!-- Google font-->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @includeIf('layouts.customer.partials.css')
</head>
<body>
    <!-- Loader -->
    <div class="loader-wrapper">
        <div class="theme-loader"></div>
    </div>

    <!-- Page Wrapper -->
    <div class="page-wrapper compact-wrapper" id="pageWrapper">
        <!-- Header -->
        @includeIf('layouts.customer.partials.header')

        <!-- Page Body -->
        <div class="page-body-wrapper sidebar-icon">
            <!-- Sidebar -->
            @includeIf('layouts.customer.partials.sidebar')

            <!-- Main Content -->
            <div class="page-body">
                @yield('content')
            </div>

            <!-- Footer -->
            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-6 footer-copyright">
                            <p class="mb-0">Copyright {{ date('Y') }} © Rapid Rebuilt LLC. All rights reserved.</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <p class="mb-0">
                                <a href="{{ route('customer.support') ?? '#' }}">Support</a> |
                                <a href="{{ route('customer.faq') ?? '#' }}">FAQ</a>
                            </p>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    @includeIf('layouts.customer.partials.js')
    @stack('scripts')
</body>
</html>
