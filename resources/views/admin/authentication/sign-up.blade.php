@extends('admin.authentication.master')

@section('title')Sign Up
{{ $title }}
@endsection

@push('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/sweetalert2.css') }}">
@endpush



@section('content')
<section>
	<div class="container-fluid p-0">
		<div class="row m-0">
			<div class="col-xl-5"><img class="bg-img-cover bg-center" src="{{ asset('assets/images/login/3.jpg') }}" alt="looginpage" /></div>
			<div class="col-xl-7 p-0">
				<div class="login-card2">
					<form class="theme-form login-form" method="POST" action="{{ route('register') }}">
						@csrf
						<a href="#"><img class="img-logo" src="{{asset('assets/images/logo/rapid.png')}}" alt=""></a>
						<h4>Create your account</h4>
						<h6>Please select the type of user:</h6>

						<!-- show error at top of the form -->

						<!-- @if ($errors->any())
						<div class="alert alert-danger">
							<ul>
								@foreach ($errors->all() as $error)
								<li>{{ $error }}</li>
								@endforeach
							</ul>
						</div>
						@endif -->

						<div class="form-group">
							<div class="btn-group w-100" role="group">
								<input type="radio" class="btn-check" name="user_type" id="user" value="user" checked>
								<label class="btn btn-outline-primary me-5" for="user">Register as User</label> <!-- Add margin-end -->

								<input type="radio" class="btn-check" name="user_type" id="dealer" value="dealer">
								<label class="btn btn-outline-primary" for="dealer">Register as Dealer</label>
							</div>
						</div>

						<!-- Dealer-specific fields (initially hidden) -->
						<div id="dealer-fields" style="display: none;">
							<div class="row">
								<div class="col-md-4">
									<div class="form-group">
										<label>County</label>
										<input type="text" class="form-control" name="county" placeholder="County" value="{{ old('county') }}">
									</div>
								</div>
								<div class="col-md-8">
									<div class="form-group">
										<label>Dealer Name</label>
										<input type="text" class="form-control" name="dealer_name" placeholder="Dealer Name" value="{{ old('dealer_name') }}">
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-4">
									<div class="form-group">
										<label>License Type</label>
										<select name="license_type" class="form-control" value="{{ old('license_type') }}">
											<option value="" disabled selected>Select License Type</option>
											<option value="VI">VI</option>
											<option value="VW">VW</option>
											<option value="VA">VA</option>
											<option value="SD">SD</option>
											<option value="VF">VF</option>
										</select>
									</div>
								</div>
								<div class="col-md-8">
									<div class="form-group">
										<label>License Number</label>
										{{-- Not type=number: a real licence carries a suffix, as in 1142356-1, which a number input will not accept. --}}
										<input type="text" class="form-control" name="license_number" placeholder="e.g. 1142356-1" value="{{ old('license_number') }}">
									</div>
								</div>
								</div>
								<div class="row">
									<div class="col-md-12">
										<div class="form-group">
											<label>Sales Tax Registration Number <span class="text-muted">(optional)</span></label>
											<input type="text" class="form-control" name="sales_tax_registration_number" placeholder="e.g. 78-8012345678-4" value="{{ old('sales_tax_registration_number') }}">
											<small class="form-text text-muted">Florida dealers: the 13-digit number from your Certificate of Registration (DR-11), e.g. 78-8012345678-4. Printed in Section 7 of the title application. You can add this later.</small>
										</div>
									</div>
								</div>
							</div>

						<!-- Common fields -->
						<div class="row">
							<div class="col-md-4">
								<div class="form-group">
									<label>First Name</label>
									<input type="text" class="form-control" name="first_name" required placeholder="First Name" value="{{ old('first_name') }}">
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Last Name</label>
									<input type="text" class="form-control" name="last_name" required placeholder="Last Name" value="{{ old('last_name') }}">
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Email Address</label>
									<input type="email" class="form-control" name="email" required placeholder="Email Address" value="{{ old('email') }}">
									@error('email')
									<span class="text-danger">{{ $message }}</span>
									@enderror
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-md-4">
								<div class="form-group">
									<label>Street Address</label>
									<input type="text" class="form-control" name="address_1" required placeholder="Street Address" value="{{ old('address_1') }}">
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Street Address 2</label>
									<input type="text" class="form-control" name="address_2" placeholder="Street Address 2" value="{{ old('address_2') }}">
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Phone</label>
									<input type="tel" class="form-control" name="phone" required placeholder="Phone Number" value="{{ old('phone') }}">
									@error('phone')
									<span class="text-danger">{{ $message }}</span>
									@enderror
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-md-4">
								<div class="form-group">
									<label>City</label>
									<input type="text" class="form-control" name="city" required placeholder="City" value="{{ old('city') }}">
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>State</label>
									<input type="text" class="form-control" name="states" required placeholder="State" value="{{ old('states') }}">
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>ZIP Code</label>
									<input type="text" class="form-control" name="zip" required placeholder="ZIP Code" value="{{ old('zip') }}">
								</div>
							</div>
						</div>

						<!-- Dealer-specific additional fields -->
						<div id="dealer-additional-fields" style="display: none;">
							<div class="row">
								<div class="col-md-4">
									<div class="form-group">
										<label>Region Number</label>
										<input type="number" class="form-control" name="region" placeholder="Region Number" value="{{ old('region') }}">
									</div>
								</div>
								<div class="col-md-4">
									<div class="form-group">
										<label>Zone</label>
										<input type="text" class="form-control" name="zones" placeholder="Zone" value="{{ old('zones') }}">
									</div>
								</div>
								<div class="col-md-4">
									<div class="form-group">
										<label>Additional Info</label>
										<input type="text" class="form-control" name="additional_info" placeholder="Additional Info" value="{{ old('additional_info') }}">
									</div>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-md-4">
								<div class="form-group">
									<label>Username</label>
									<input type="text" class="form-control" name="username" required placeholder="Choose a username" value="{{ old('phone') }}">
									@error('username')
									<span class="text-danger">{{ $message }}</span>
									@enderror
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Password</label>
									<input type="password" class="form-control" name="password" required placeholder="Create a password" value="{{ old('password') }}">
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Confirm Password</label>
									<input type="password" class="form-control" name=" password_confirmation" required placeholder="Confirm your password" value="{{ old('password_confirmation') }}">
								</div>
							</div>
						</div>

						<div class="form-group">
							<div class="checkbox">
								<input id="checkbox1" type="checkbox" required />
								<label class="text-muted" for="checkbox1">Agree with <span>Privacy Policy</span></label>
							</div>
						</div>

						<div class="row">
							@if (config('services.recaptcha.enabled'))
								<div class="col-md-6">
									<div class="form-group">
										<div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
										@error('g-recaptcha-response')
										<span class="text-danger">{{ $message }}</span>
										@enderror
									</div>
								</div>
							@endif
							<div class="col-md-6">
								<div class="form-group">
									<button class="btn btn-primary btn-block" type="submit">Create Account</button>
								</div>
							</div>
						</div>

						<p>Already have an account? <a class="ms-2" href="{{ route('login') }}">Sign in</a></p>
					</form>
				</div>
			</div>
		</div>
	</div>
</section>

@push('scripts')
<script src="{{ asset('assets/js/sweet-alert/sweetalert.min.js') }}"></script>
@if (config('services.recaptcha.enabled'))
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endif

<script>
	document.addEventListener('DOMContentLoaded', function() {
		const userTypeRadios = document.querySelectorAll('input[name="user_type"]');
		const dealerFields = document.getElementById('dealer-fields');
		const dealerAdditionalFields = document.getElementById('dealer-additional-fields');

		function toggleDealerFields() {
			const isDealer = document.getElementById('dealer').checked;
			dealerFields.style.display = isDealer ? 'block' : 'none';
			dealerAdditionalFields.style.display = isDealer ? 'block' : 'none';

			const dealerInputs = [...dealerFields.querySelectorAll('input, select'),
				...dealerAdditionalFields.querySelectorAll('input')
			];
			dealerInputs.forEach(input => {
				input.required = isDealer;
			});
		}

		userTypeRadios.forEach(radio => {
			radio.addEventListener('change', toggleDealerFields);
		});

		toggleDealerFields();

		const emailField = document.querySelector('input[name="email"]');
		const usernameField = document.querySelector('input[name="username"]');
		const phoneField = document.querySelector('input[name="phone"]');

		function checkFieldAvailability(field, type) {
			const value = field.value;
			if (value) {
				fetch(`/check-availability?type=${type}&value=${value}`)
					.then(response => response.json())
					.then(data => {
						if (data.exists) {
							Swal.fire({
								icon: 'error',
								title: 'Oops...',
								text: `${type} is already registered!`,
							});
							field.focus();
						}
					});
			}
		}

		emailField.addEventListener('blur', () => checkFieldAvailability(emailField, 'email'));
		usernameField.addEventListener('blur', () => checkFieldAvailability(usernameField, 'username'));
		phoneField.addEventListener('blur', () => checkFieldAvailability(phoneField, 'phone'));
	});
</script>
@endpush

@endsection