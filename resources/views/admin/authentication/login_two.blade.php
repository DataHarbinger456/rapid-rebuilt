@extends('admin.authentication.master')

@section('title')login
 {{ $title }}
@endsection

@push('css')
@endpush

@section('content')
    <section>
	    <div class="container-fluid">
	        <div class="row">
	            <div class="col-xl-5"><img class="bg-img-cover bg-center" src="{{ asset('assets/images/login/3.jpg') }}" alt="looginpage" /></div>
	            <div class="col-xl-7 p-0">
	                <div class="login-card">
					<form class="theme-form login-form" style="width: 500px !important; border-radius: 15px;">
                        <a href="{{ route('index') }}"><img class="" style="width: 35%; margin:0 0 5% 35%;" src="{{asset('assets/images/logo/rapid.png')}}" alt=""></a>
                        <h4>Login</h4>
                        <h6>Welcome DMV Form Filling System.</h6>
                        <div class="form-group">
                            <label>Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="icon-email"></i></span>
                                <input class="form-control" type="email" required="" placeholder="Test@gmail.com" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="icon-lock"></i></span>
                                <input class="form-control" type="password" name="login[password]" required="" placeholder="*********" />
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
                        
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="g-recaptcha" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}"></div>
                                    @error('g-recaptcha-response')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <button class="btn btn-primary btn-block" type="submit">Sign in</button>
                                </div>
                            </div>
                        </div>

                        <p>Don't have account?<a class="ms-2" href="{{ route('sign-up') }}">Create Account</a></p>
                    </form>
	                </div>
	            </div>
	        </div>
	    </div>
	</section>

	
    @push('scripts')
    @endpush

@endsection