<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SalesTaxNumber;
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
            // Only demanded when reCAPTCHA is configured for this domain.
            'g-recaptcha-response' => config('services.recaptcha.enabled') ? 'required' : 'nullable',
        ]);

         
        // Add dealer-specific validation rules
        if ($request->user_type === 'dealer') {
            $validator->addRules([
                'county' => 'required|string|max:255',
                'dealer_name' => 'required|string|max:255',
                'license_type' => 'required|in:VI,VW,VA,SD,VF',
                // A Florida dealer licence number is not a number. It carries a
                // suffix, as in 1142356-1, so the previous 'numeric' rule
                // rejected every real one and no dealer could register at all.
                'license_number' => ['required', 'string', 'max:255', 'regex:/^[0-9][0-9\-]*$/'],
                // Section 7 of the HSMV 82040 asks for this, but a dealer may
                // not have it to hand at signup and it is not needed until a
                // form is generated, so it is optional here rather than a
                // barrier to registering.
                //
                // A Florida dealer's number comes off a DR-11 certificate and is
                // thirteen digits, so a typo is catchable here rather than on a
                // filed form. Other states print their own formats, which we
                // cannot verify, so those stay free text.
                'sales_tax_registration_number' => [
                    'nullable',
                    'string',
                    'max:255',
                    function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                        if (trim((string) $value) === '' || !SalesTaxNumber::isFlorida($request->states)) {
                            return;
                        }

                        if (!SalesTaxNumber::isValidFlorida($value)) {
                            $count = strlen(SalesTaxNumber::digits($value));

                            $fail(
                                'A Florida sales tax registration number has '
                                . SalesTaxNumber::FLORIDA_LENGTH . ' digits, like 78-8012345678-4. '
                                . "This one has {$count}."
                            );
                        }
                    },
                ],
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
    
        // Verify reCAPTCHA when it is enabled and its keys are configured.
        if (config('services.recaptcha.enabled')) {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => config('services.recaptcha.secret'),
                'response' => $request->input('g-recaptcha-response'),
            ]);

            $responseBody = json_decode($response->getBody());

            if (! ($responseBody->success ?? false)) {
                return redirect()
                    ->back()
                    ->withErrors(['g-recaptcha-response' => 'reCAPTCHA verification failed.'])
                    ->withInput();
            }
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
                // Stored as digits for a Florida dealer, since the dashes are
                // presentation and go back on at print time. Kept verbatim for
                // any other state.
                'sales_tax_registration_number' => SalesTaxNumber::forStorage(
                    $request->sales_tax_registration_number,
                    $request->states
                ),
                'region' => $request->region,
                'zones' => $request->zones,
            ]);
        }
    
        // Create the user in the database
        $user = User::create($userData);
    
        // Log in the user
        auth()->login($user);
    
        // Redirect to the index route
        return redirect()->route('index');
    }
}
