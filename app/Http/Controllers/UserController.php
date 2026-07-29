<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User; // Make sure to import the User model

class UserController extends Controller
{
    public function checkAvailability(Request $request)
    {
        $type = $request->query('type');
        $value = $request->query('value');

        $exists = false;

        switch ($type) {
            case 'email':
                $exists = User::where('email', $value)->exists();
                break;
            case 'username':
                $exists = User::where('username', $value)->exists();
                break;
            case 'phone':
                $exists = User::where('phone', $value)->exists();
                break;
        }

        return response()->json(['exists' => $exists]);
    }
}