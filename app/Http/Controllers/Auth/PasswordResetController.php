<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    // Mostrar el formulario de recuperación de contraseña
    public function showForgetPasswordForm()
    {
        return view('admin.authentication.forget-password'); // Asegúrate de que esta vista exista
    }

    // Manejar la solicitud de recuperación de contraseña
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Enviar el enlace de restablecimiento de contraseña
        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }
}