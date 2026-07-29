<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User; // Import the User model

class LoginController extends Controller
{
    // Método para iniciar sesión
    public function login(Request $request)
    {
        // Validar la solicitud
        $rules = [
            'email' => 'required|email',
            'password' => 'required',
        ];

        // Solo validar reCAPTCHA si está habilitado
        $recaptchaEnabled = config('services.recaptcha.enabled', false);
        if ($recaptchaEnabled) {
            $rules['g-recaptcha-response'] = 'required';
        }

        $request->validate($rules);

        // Check if the email exists
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return redirect()->back()->withErrors(['email' => 'Email is not registered, Please Create an Account.'])->withInput();
        }

        // Verificar reCAPTCHA solo si está habilitado
        if ($recaptchaEnabled) {
            $response = Http::withOptions([
                'verify' => false,
                'timeout' => 30,
                'connect_timeout' => 10,
                'force_ip_resolve' => 'v4',
            ])->asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => config('services.recaptcha.secret'),
                'response' => $request->input('g-recaptcha-response'),
            ]);

            $responseBody = json_decode($response->getBody());

            if (!$responseBody || !$responseBody->success) {
                $errorCodes = $responseBody->{'error-codes'} ?? ['unknown'];
                Log::warning('reCAPTCHA Failed:', ['errors' => $errorCodes]);
                return redirect()->back()->withErrors(['g-recaptcha-response' => 'reCAPTCHA verification failed: ' . implode(', ', $errorCodes)])->withInput();
            }
        }

        // Attempt to log the user in
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            // Redirect to intended page
            return redirect()->intended('/dashboard'); // Change '/dashboard' to your actual dashboard route
        }

        // If login fails, redirect back with an error
        return redirect()->back()->withErrors(['password' => 'The provided password is incorrect.'])->withInput();
    }
    // Método para cerrar sesión
    public function logout(Request $request)
    {
        Auth::logout(); // Cierra la sesión del usuario
        return redirect()->route('login.form'); // Redirige al login
    }
}
