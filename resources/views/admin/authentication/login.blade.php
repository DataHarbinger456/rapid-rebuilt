@extends('admin.authentication.master')

@section('title')Login
{{ $title }}
@endsection

@push('css')
@endpush

@section('content')
<section>
    <div class="container-fluid p-0">
        <div class="row">
            <div class="col-12">
                <div class="login-card">
                    <form method="POST" action="{{ route('login') }}" class="theme-form login-form" style="width: 500px !important; border-radius: 15px;">
                        @csrf
                        <a href="#"><img class="" style="width: 35%; margin:0 0 5% 35%;" src="{{ asset('assets/images/logo/rapid.png') }}" alt=""></a>
                        <h4>Login</h4>
                        <h6>Welcome DMV Form Filling System.</h6>

                        @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <div class="form-group">
                            <label>Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="icon-email"></i></span>
                                <input class="form-control" type="email" name="email" required placeholder="mail@mail.com" value="{{ old('email') }}" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="icon-lock"></i></span>
                                <input class="form-control" type="password" name="password" required placeholder="*********" />
                                <div class="show-hide"><span class="show"> </span></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="checkbox">
                                <input id="checkbox1" type="checkbox" />
                                <label for="checkbox1">Remember password</label>
                            </div>
                            <a class="link" href="{{ route('forget-password') }}">Forgot password?</a>
                        </div>

                        <div class="row">
                            @if(config('services.recaptcha.enabled'))
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                                    @error('g-recaptcha-response')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            @endif
                            <div class="{{ config('services.recaptcha.enabled') ? 'col-md-6' : 'col-12' }}">
                                <div class="form-group">
                                    <button class="btn btn-primary btn-block" type="submit">Sign in</button>
                                </div>
                            </div>
                        </div>

                        <p>Don't have an account? <a class="ms-2" href="{{ route('sign-up') }}">Create Account</a></p>
                        <p>Or <a href="{{ route('magic.form') }}">email me a sign-in link</a> instead.</p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

@if(config('services.recaptcha.enabled'))
@push('scripts')
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endpush
@endif

@endsection