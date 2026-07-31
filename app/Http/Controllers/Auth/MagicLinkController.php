<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class MagicLinkController extends Controller
{
    /**
     * Show the magic-link request form.
     */
    public function showForm()
    {
        return view('auth.magic-link');
    }

    /**
     * Email a temporary signed login link to the user.
     */
    public function send(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $url = URL::temporarySignedRoute(
                'magic.login',
                now()->addMinutes(30),
                ['user' => $user->id]
            );

            Mail::raw(
                "Hi {$user->first_name},\n\n"
                . "Click the link below to sign in to Rapid Rebuilt. It expires in 30 minutes.\n\n"
                . "{$url}\n\n"
                . "If you didn't request this, you can ignore this email.",
                function ($message) use ($user) {
                    $message->to($user->email)->subject('Your Rapid Rebuilt sign-in link');
                }
            );
        }

        // Same response either way — don't leak which emails exist.
        return back()->with('success', 'If that email belongs to an account, a sign-in link is on its way.');
    }

    /**
     * Log the user in from a valid signed link.
     */
    public function login(Request $request, User $user)
    {
        Auth::login($user);
        $request->session()->regenerate();

        return $user->isCustomer()
            ? redirect()->route('customer.dashboard')
            : redirect()->route('dashboard');
    }
}
