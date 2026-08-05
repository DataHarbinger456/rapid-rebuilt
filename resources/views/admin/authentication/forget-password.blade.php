@extends('admin.authentication.master')

@section('title')Forget Password
@endsection

@section('content')
    <section>
        <div class="container-fluid p-0">
            <div class="row m-0">
                <div class="col-12 p-0">
                    <div class="login-card">
                        <div class="login-main">
                            <form method="POST" action="{{ route('forget-password.send') }}" class="theme-form login-form" style="border-radius: 15px !important;">
                                @csrf
                                <a href="{{ route('login.form') }}"><img class="" style="width: 150px; margin:0 0 5% 35%;" src="{{ asset('assets/images/logo/rapid.png') }}" alt=""></a>
                                <h4 class="mb-3">Reset Your Password</h4>
                                <h6>Enter your email and we'll send you a reset link.</h6>

                                @if (session('status'))
                                <div class="alert alert-success">{{ session('status') }}</div>
                                @endif

                                @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
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
                                    <button class="btn btn-primary btn-block" type="submit">Send Reset Link</button>
                                </div>
                                <p>Already have a password?<a class="ms-2" href="{{ route('login.form') }}">Sign in</a></p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
