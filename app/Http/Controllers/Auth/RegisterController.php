<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Traits\ValidationHelperTrait;
use App\Utilities\LoggingUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Intervention\Validation\Rules\Username;
use JonPurvis\Squeaky\Rules\Clean;
use LaraUtilX\Http\Controllers\CrudController;
use Propaganistas\LaravelPhone\Rules\Phone;
use Ziming\LaravelZxcvbn\Rules\ZxcvbnRule;

class RegisterController extends CrudController
{
    use ValidationHelperTrait;

    public function __construct()
    {
        parent::__construct(new User());
    }

    /**
     * Show the registration form.
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Handle a registration request.
     */
    public function register(Request $request)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', new Username(), new Clean()],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => [
                'required',
                'confirmed',
                'min:8',
                new ZxcvbnRule([
                    $request->email,
                    $request->name,
                ]),
            ],
            'phone' => ['nullable', 'string'],
            'phone_country' => ['nullable', 'string', 'size:2'],
        ];

        // Add phone validation if phone number is provided
        if (! empty($request->phone)) {
            if ($request->phone_country) {
                $rules['phone'][] = (new Phone())->country($request->phone_country);
            } else {
                $rules['phone'][] = new Phone();
            }
            $rules['phone_country'][] = 'required_with:phone';
        }

        $validated = $this->validateRequestData($request, $rules);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'phone_country' => $validated['phone_country'] ?? null,
        ]);

        Auth::login($user);

        LoggingUtil::info('User registration successful', [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'has_phone' => ! empty($user->phone),
        ], 'auth');

        return redirect()
            ->route('game.dashboard')
            ->with('success', 'Welcome to Travian Online! Your account has been created successfully.');
    }
}
