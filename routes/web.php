<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DMVFormController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\TrackerController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Include additional routes from admin_web.php
require __DIR__.'/admin_web.php';

// Include customer portal routes
require __DIR__.'/customer_web.php';

// Test route for debugging (can be removed later)
Route::get('/test-route', function () {
    return response()->json(['success' => true, 'message' => 'Routes are working!']);
});

// Redirect root to index route
Route::get('/', function () {
    return redirect()->route('index'); // Ensure 'index' route is defined
})->name('/');

// Define the index route
Route::get('/index', function () {
    return redirect()->route('dashboard'); // Redirect to the dashboard route
})->name('index');

// Sample page route
Route::view('sample-page', 'admin.pages.sample-page')->name('sample-page');

// Authenticated routes
Route::middleware(['auth'])->group(function () {
    // Dashboard route
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard'); // Ensure this route exists

    // DMV Form routes
    Route::get('/dmv-forms/{formType}', [DMVFormController::class, 'create'])->name('forms.create');
    Route::post('/dmv-forms/{formType}', [DMVFormController::class, 'store'])->name('forms.store');
    Route::get('/dmv-forms', [DMVFormController::class, 'list'])->name('forms.list');

    // Session-auth routes whose names match existing blade references (api.*)
    Route::get('/documents/{document}/download', [\App\Http\Controllers\Customer\DocumentController::class, 'download'])
        ->name('api.documents.download');
    Route::get('/documents/{document}/view', [\App\Http\Controllers\Customer\DocumentController::class, 'view'])
        ->name('api.documents.view');
    Route::get('/documents/{document}/thumbnail', [\App\Http\Controllers\Customer\DocumentController::class, 'view'])
        ->name('api.documents.thumbnail');
    Route::get('/appointments/slots', [\App\Http\Controllers\Api\AppointmentController::class, 'availableSlots'])
        ->name('api.appointments.slots');

    // Help & Manual routes
    Route::get('/help/manual', [HelpController::class, 'manual'])->name('help.manual');
    Route::get('/help/topic/{topic}', [HelpController::class, 'topic'])->name('help.topic');
});

// Login routes
Route::get('/login', function () {
    return view('admin.authentication.login');
})->name('login.form');

Route::post('/login', [LoginController::class, 'login'])->name('login'); // This is the POST route for login

// Logout route
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Public case tracker (signed URL, no auth)
Route::get('/track/{case}', [TrackerController::class, 'show'])
    ->middleware('signed')
    ->name('case.track');

// Magic-link (passwordless) login routes
Route::get('/magic-link', [MagicLinkController::class, 'showForm'])->name('magic.form');
Route::post('/magic-link', [MagicLinkController::class, 'send'])->name('magic.send');
Route::get('/magic-login/{user}', [MagicLinkController::class, 'login'])
    ->middleware('signed')
    ->name('magic.login');

// Registration routes
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Route to show the password reset form
Route::get('/forget-password', [PasswordResetController::class, 'showForgetPasswordForm'])->name('forget-password');

// Route to handle the password reset link request
Route::post('/forget-password', [PasswordResetController::class, 'sendResetLink'])->name('forget-password.send');

// Target of the emailed reset link. The `password.reset` name is required:
// Laravel's ResetPassword notification builds its URL from it.
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');

// Route to store the new password
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');

// Route to check Availability of the email, usename, and phone number
Route::get('/check-availability', [UserController::class, 'checkAvailability']);

// Health check for uptime monitoring: app is booted, verify DB connectivity.
Route::get('/healthz', function () {
    try {
        \Illuminate\Support\Facades\DB::select('SELECT 1');
        $db = 'ok';
    } catch (\Throwable) {
        $db = 'error';
    }

    return response()->json(
        ['app' => 'ok', 'db' => $db],
        $db === 'ok' ? 200 : 503
    );
})->name('healthz');