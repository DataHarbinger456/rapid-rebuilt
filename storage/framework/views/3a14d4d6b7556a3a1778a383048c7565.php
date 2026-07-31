<?php $__env->startSection('title'); ?>Sign Up
<?php echo e($title); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('css'); ?>
<link rel="stylesheet" type="text/css" href="<?php echo e(asset('assets/css/sweetalert2.css')); ?>">
<?php $__env->stopPush(); ?>



<?php $__env->startSection('content'); ?>
<section>
	<div class="container-fluid p-0">
		<div class="row m-0">
			<div class="col-xl-5"><img class="bg-img-cover bg-center" src="<?php echo e(asset('assets/images/login/3.jpg')); ?>" alt="looginpage" /></div>
			<div class="col-xl-7 p-0">
				<div class="login-card2">
					<form class="theme-form login-form" method="POST" action="<?php echo e(route('register')); ?>">
						<?php echo csrf_field(); ?>
						<a href="#"><img class="img-logo" src="<?php echo e(asset('assets/images/logo/rapid.png')); ?>" alt=""></a>
						<h4>Create your account</h4>
						<h6>Please select the type of user:</h6>

						<!-- show error at top of the form -->

						<!-- <?php if($errors->any()): ?>
						<div class="alert alert-danger">
							<ul>
								<?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
								<li><?php echo e($error); ?></li>
								<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
							</ul>
						</div>
						<?php endif; ?> -->

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
										<input type="text" class="form-control" name="county" placeholder="County" value="<?php echo e(old('county')); ?>">
									</div>
								</div>
								<div class="col-md-8">
									<div class="form-group">
										<label>Dealer Name</label>
										<input type="text" class="form-control" name="dealer_name" placeholder="Dealer Name" value="<?php echo e(old('dealer_name')); ?>">
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-4">
									<div class="form-group">
										<label>License Type</label>
										<select name="license_type" class="form-control" value="<?php echo e(old('license_type')); ?>">
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
										<input type="number" class="form-control" name="license_number" placeholder="License Number" value="<?php echo e(old('license_number')); ?>">
									</div>
								</div>
							</div>
						</div>

						<!-- Common fields -->
						<div class="row">
							<div class="col-md-4">
								<div class="form-group">
									<label>First Name</label>
									<input type="text" class="form-control" name="first_name" required placeholder="First Name" value="<?php echo e(old('first_name')); ?>">
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Last Name</label>
									<input type="text" class="form-control" name="last_name" required placeholder="Last Name" value="<?php echo e(old('last_name')); ?>">
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Email Address</label>
									<input type="email" class="form-control" name="email" required placeholder="Email Address" value="<?php echo e(old('email')); ?>">
									<?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
									<span class="text-danger"><?php echo e($message); ?></span>
									<?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-md-4">
								<div class="form-group">
									<label>Street Address</label>
									<input type="text" class="form-control" name="address_1" required placeholder="Street Address" value="<?php echo e(old('address_1')); ?>">
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Street Address 2</label>
									<input type="text" class="form-control" name="address_2" placeholder="Street Address 2" value="<?php echo e(old('address_2')); ?>">
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Phone</label>
									<input type="tel" class="form-control" name="phone" required placeholder="Phone Number" value="<?php echo e(old('phone')); ?>">
									<?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
									<span class="text-danger"><?php echo e($message); ?></span>
									<?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-md-4">
								<div class="form-group">
									<label>City</label>
									<input type="text" class="form-control" name="city" required placeholder="City" value="<?php echo e(old('city')); ?>">
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>State</label>
									<input type="text" class="form-control" name="states" required placeholder="State" value="<?php echo e(old('states')); ?>">
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>ZIP Code</label>
									<input type="text" class="form-control" name="zip" required placeholder="ZIP Code" value="<?php echo e(old('zip')); ?>">
								</div>
							</div>
						</div>

						<!-- Dealer-specific additional fields -->
						<div id="dealer-additional-fields" style="display: none;">
							<div class="row">
								<div class="col-md-4">
									<div class="form-group">
										<label>Region Number</label>
										<input type="number" class="form-control" name="region" placeholder="Region Number" value="<?php echo e(old('region')); ?>">
									</div>
								</div>
								<div class="col-md-4">
									<div class="form-group">
										<label>Zone</label>
										<input type="text" class="form-control" name="zones" placeholder="Zone" value="<?php echo e(old('zones')); ?>">
									</div>
								</div>
								<div class="col-md-4">
									<div class="form-group">
										<label>Additional Info</label>
										<input type="text" class="form-control" name="additional_info" placeholder="Additional Info" value="<?php echo e(old('additional_info')); ?>">
									</div>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-md-4">
								<div class="form-group">
									<label>Username</label>
									<input type="text" class="form-control" name="username" required placeholder="Choose a username" value="<?php echo e(old('phone')); ?>">
									<?php $__errorArgs = ['username'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
									<span class="text-danger"><?php echo e($message); ?></span>
									<?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Password</label>
									<input type="password" class="form-control" name="password" required placeholder="Create a password" value="<?php echo e(old('password')); ?>">
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Confirm Password</label>
									<input type="password" class="form-control" name=" password_confirmation" required placeholder="Confirm your password" value="<?php echo e(old('password_confirmation')); ?>">
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
							<div class="col-md-6">
								<div class="form-group">
									<div class="g-recaptcha" data-sitekey="6Ld7mMIqAAAAAIoaeEhu4w9rJY-mmMqGmRKIRNNv"></div>
									<?php $__errorArgs = ['g-recaptcha-response'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
									<span class="text-danger"><?php echo e($message); ?></span>
									<?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group">
									<button class="btn btn-primary btn-block" type="submit">Create Account</button>
								</div>
							</div>
						</div>

						<p>Already have an account? <a class="ms-2" href="<?php echo e(route('login')); ?>">Sign in</a></p>
					</form>
				</div>
			</div>
		</div>
	</div>
</section>

<?php $__env->startPush('scripts'); ?>
<script src="<?php echo e(asset('assets/js/sweet-alert/sweetalert.min.js')); ?>"></script>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>

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
<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('admin.authentication.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/studiomac/gg-projects/rapid-rebuilt/resources/views/admin/authentication/sign-up.blade.php ENDPATH**/ ?>