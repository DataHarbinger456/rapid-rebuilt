@extends('admin.authentication.master')
@section('title')Email Verification
@endsection
@section('content')
<section>
    <div class="container-fluid p-0">
        <div class="row m-0">
            <div class="col-xl-5">
                <img class="bg-img-cover bg-center" src="{{ asset('assets/images/login/3.jpg') }}" alt="verification page" />
            </div>
            <div class="col-xl-7 p-0">
                <div class="login-card2">
                    <form class="theme-form login-form" method="POST" action="{{ route('verify-email') }}">
                        @csrf
                        <a href="{{ route('index') }}">
                            <img class="img-logo" src="{{ asset('assets/images/logo/rapid.png') }}" alt="">
                        </a>
                        <h4>Email Verification</h4>
                        <p>Please enter the verification token sent to your email</p>
                        <div class="form-group">
                            <label>Verification Token</label>
                            <input type="text" class="form-control" name="verification_token" required placeholder="Enter verification token">
                        </div>

                        <div class="form-group">
                            <button class="btn btn-primary btn-block" type="submit">Verify</button>
                        </div>

                        <p>Didn't receive the token? <a href="{{ route('resend-verification') }}">Resend</a></p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var errorMessage = "{{ session('error') }}";
        if (errorMessage) {
            swal("Error", errorMessage, "error");
        }
    });
</script>
@endpush