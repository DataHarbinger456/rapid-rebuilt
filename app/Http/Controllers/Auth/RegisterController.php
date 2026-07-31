<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class RegisterController extends Controller
{
    public function showRegistrationForm()
    {

        return view('admin.authentication.sign-up');
    }


    public function register(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'user_type' => 'required|in:user,dealer',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'address_1' => 'required|string|max:255',
            'address_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'states' => 'required|string|max:255',
            'zip' => 'required|string|max:20',
            'phone' => 'required|string|max:20|unique:users',
            'g-recaptcha-response' => 'required',
        ]);

         
        // Add dealer-specific validation rules
        if ($request->user_type === 'dealer') {
            $validator->addRules([
                'county' => 'required|string|max:255',
                'dealer_name' => 'required|string|max:255',
                'license_type' => 'required|in:VI,VW,VA,SD,VF',
                'license_number' => 'required|numeric',
                'region' => 'required|numeric',
                'zones' => 'required|string|max:255',
            ]);
        }
    
        // Check if validation fails
        if ($validator->fails()) {
            Log::error('Registration validation failed', $validator->errors()->toArray());
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }
    
        // Verify reCAPTCHA
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => "6Ld7mMIqAAAAANyqqwLez3i8S01D-XrYiuLm1x56",
            'response' => $request->input('g-recaptcha-response'),
        ]);
        
        $responseBody = json_decode($response->getBody());
    
        // Check if reCAPTCHA verification failed
        if (!$responseBody->success) {
            return redirect()
                ->back()
                ->withErrors(['g-recaptcha-response' => 'reCAPTCHA verification failed.'])
                ->withInput();
        }
    
        // Create the user
        $userData = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'user_type' => $request->user_type,
            'address_1' => $request->address_1,
            'address_2' => $request->address_2,
            'city' => $request->city,
            'states' => $request->states,
            'zip' => $request->zip,
            'phone' => $request->phone,
        ];
    
        // Add dealer-specific data
        if ($request->user_type === 'dealer') {
            $userData = array_merge($userData, [
                'county' => $request->county,
                'dealer_name' => $request->dealer_name,
                'license_type' => $request->license_type,
                'license_number' => $request->license_number,
                'region' => $request->region,
                'zones' => $request->zones,
            ]);
        }
    
        // Create the user in the database
        $user = User::create($userData);
    
        // Generate a verification token
        $token = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->verification_token = $token;
        $user->save();
    
        // Log in the user
        auth()->login($user);
    
        // Redirect to the index route
        return redirect()->route('index');
    }
}
