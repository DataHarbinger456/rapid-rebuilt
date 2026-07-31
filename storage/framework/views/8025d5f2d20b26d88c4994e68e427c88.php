<?php $__env->startSection('title'); ?>Login
<?php echo e($title); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('css'); ?>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<section>
    <div class="container-fluid p-0">
        <div class="row">
            <div class="col-12">
                <div class="login-card">
                    <form method="POST" action="<?php echo e(route('login')); ?>" class="theme-form login-form" style="width: 500px !important; border-radius: 15px;">
                        <?php echo csrf_field(); ?>
                        <a href="#"><img class="" style="width: 35%; margin:0 0 5% 35%;" src="<?php echo e(asset('assets/images/logo/rapid.png')); ?>" alt=""></a>
                        <h4>Login</h4>
                        <h6>Welcome DMV Form Filling System.</h6>

                        <?php if($errors->any()): ?>
                        <div class="alert alert-danger">
                            <ul>
                                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($error); ?></li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label>Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="icon-email"></i></span>
                                <input class="form-control" type="email" name="email" required placeholder="mail@mail.com" value="<?php echo e(old('email')); ?>" />
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
                            <a class="link" href="<?php echo e(route('forget-password')); ?>">Forgot password?</a>
                        </div>

                        <div class="row">
                            <?php if(config('services.recaptcha.enabled')): ?>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="g-recaptcha" data-sitekey="<?php echo e(config('services.recaptcha.site_key')); ?>"></div>
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
                            <?php endif; ?>
                            <div class="<?php echo e(config('services.recaptcha.enabled') ? 'col-md-6' : 'col-12'); ?>">
                                <div class="form-group">
                                    <button class="btn btn-primary btn-block" type="submit">Sign in</button>
                                </div>
                            </div>
                        </div>

                        <p>Don't have an account? <a class="ms-2" href="<?php echo e(route('sign-up')); ?>">Create Account</a></p>
                        <p>Or <a href="<?php echo e(route('magic.form')); ?>">email me a sign-in link</a> instead.</p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if(config('services.recaptcha.enabled')): ?>
<?php $__env->startPush('scripts'); ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php $__env->stopPush(); ?>
<?php endif; ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('admin.authentication.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/studiomac/gg-projects/rapid-rebuilt/resources/views/admin/authentication/login.blade.php ENDPATH**/ ?>