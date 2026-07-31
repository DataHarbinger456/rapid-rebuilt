@extends('admin.authentication.master')

@section('title')Sign in with Magic Link
{{ $title ?? '' }}
@endsection

@push('css')
@endpush

@section('content')
<section>
    <div class="container-fluid p-0">
        <div class="row">
            <div class="col-12">
                <div class="login-card">
                    <form method="POST" action="{{ route('magic.send') }}" class="theme-form login-form" style="width: 500px !important; border-radius: 15px;">
                        @csrf
                        <a href="#"><img class="" style="width: 35%; margin:0 0 5% 35%;" src="{{ asset('assets/images/logo/rapid.png') }}" alt=""></a>
                        <h4>Sign in with a magic link</h4>
                        <h6>We'll email you a one-time sign-in link. No password needed.</h6>

                        @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

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
                            <button class="btn btn-primary btn-block" type="submit">Email me a sign-in link</button>
                        </div>

                        <p>Prefer a password? <a class="ms-2" href="{{ route('login.form') }}">Sign in normally</a></p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
