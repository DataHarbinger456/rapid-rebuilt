<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationMail; // Asegúrate de tener esta clase de correo creada
use Illuminate\Support\Str;

class VerificationController extends Controller
{
    public function verify(Request $request)
    {
        $token = $request->input('verification_token');
        
        // Find user by verification token
        $user = User::where('verification_token', $token)->first();

        if ($user) {
            // Mark email as verified
            $user->email_verified_at = now();
            $user->verification_token = null; // Clear the verification token
            $user->save();

            // Login the user
            Auth::login($user);

            // Redirect to dashboard
            return redirect()->route('dashboard')->with('success', 'Email verified successfully');
        }

        // Token not found
        return back()->with('error', 'Invalid verification token');
    }

    public function resendVerificationToken()
    {
        $user = Auth::user();
        
        // Generate a new unique token
        $token = Str::random(40); // Genera un token único de 40 caracteres
        
        $user->verification_token = $token;
        $user->save();

        // Send verification email
        Mail::to($user->email)->send(new VerificationMail($token));

        return back()->with('success', 'New verification token sent');
    }
}