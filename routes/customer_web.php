<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Customer\DashboardController;
use App\Http\Controllers\Customer\CaseController;
use App\Http\Controllers\Customer\DocumentController;
use App\Http\Controllers\Customer\AppointmentController;
use App\Http\Controllers\Customer\SettingsController;

/*
|--------------------------------------------------------------------------
| Customer Web Routes
|--------------------------------------------------------------------------
|
| Routes for the customer portal. All routes are prefixed with 'customer'
| and require authentication.
|
*/

Route::prefix('customer')->name('customer.')->middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Cases
    Route::prefix('cases')->name('cases.')->group(function () {
        Route::get('/', [CaseController::class, 'index'])->name('index');
        Route::get('/create', [CaseController::class, 'create'])->name('create');
        Route::post('/', [CaseController::class, 'store'])->name('store');
        Route::get('/{case}', [CaseController::class, 'show'])->name('show');
        Route::get('/{case}/edit', [CaseController::class, 'edit'])->name('edit');
        Route::put('/{case}', [CaseController::class, 'update'])->name('update');
        Route::delete('/{case}', [CaseController::class, 'destroy'])->name('destroy');

        // Case-specific actions
        Route::get('/{case}/documents', [CaseController::class, 'documents'])->name('documents');
        Route::get('/{case}/schedule', [CaseController::class, 'schedule'])->name('schedule');
        Route::get('/{case}/pay', [CaseController::class, 'pay'])->name('pay');
        Route::post('/{case}/pay', [CaseController::class, 'processPayment'])->name('process-payment');
        Route::get('/{case}/payment-success', [CaseController::class, 'paymentSuccess'])->name('payment-success');
    });

    // Documents
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        Route::post('/upload/{case}', [DocumentController::class, 'upload'])->name('upload');
        Route::get('/{document}/reupload', [DocumentController::class, 'reupload'])->name('reupload');
        Route::post('/{document}/reupload', [DocumentController::class, 'processReupload'])->name('process-reupload');
    });

    // Appointments
    Route::prefix('appointments')->name('appointments.')->group(function () {
        Route::get('/', [AppointmentController::class, 'index'])->name('index');
        Route::post('/', [AppointmentController::class, 'store'])->name('store');
        Route::get('/{appointment}', [AppointmentController::class, 'show'])->name('show');
        Route::get('/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('reschedule');
        Route::put('/{appointment}/reschedule', [AppointmentController::class, 'processReschedule'])->name('process-reschedule');
        Route::post('/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('cancel');
    });

    // Payments
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', function () {
            // Placeholder - implement payment history
            return view('customer.payments.index');
        })->name('index');
    });

    // Messages
    Route::get('/messages', function () {
        // Placeholder - implement messages
        return view('customer.messages.index');
    })->name('messages');

    Route::get('/messages/create', function () {
        // Placeholder - implement message creation
        return view('customer.messages.create');
    })->name('messages.create');

    // Profile & Settings
    Route::get('/profile', function () {
        return view('customer.profile');
    })->name('profile');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::post('/settings/preferences', [SettingsController::class, 'updatePreferences'])->name('settings.preferences');

    // Support & FAQ
    Route::get('/support', function () {
        return view('customer.support');
    })->name('support');

    Route::get('/faq', function () {
        return view('customer.faq');
    })->name('faq');

    // Notifications
    Route::get('/notifications', function () {
        return view('customer.notifications');
    })->name('notifications');
});
