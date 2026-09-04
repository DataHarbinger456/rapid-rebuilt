<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function index()
    {
        return view('customer.settings', ['user' => auth()->user()]);
    }

    /**
     * User has no `hashed` cast, so the hash is made explicitly here — same as
     * Admin\SettingsController and PasswordResetController.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update(['password' => Hash::make($validated['password'])]);

        return redirect()->route('customer.settings')->with('password_success', 'Your password has been changed.');
    }

    /**
     * The form posts a hidden "0" fallback when the checkbox is unticked; that
     * string is truthy, so the value is validated as boolean and cast before
     * it is stored.
     */
    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'email_notifications' => ['required', 'boolean'],
        ]);

        $request->user()->setPreference('notifications.email', (bool) $validated['email_notifications']);

        return redirect()->route('customer.settings')->with('preferences_success', 'Your notification preferences have been saved.');
    }
}
