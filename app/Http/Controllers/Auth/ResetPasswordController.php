<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Traits\ValidationHelperTrait;
use App\Utilities\LoggingUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LaraUtilX\Http\Controllers\CrudController;
use Ziming\LaravelZxcvbn\Rules\ZxcvbnRule;

class ResetPasswordController extends CrudController
{
    use ValidationHelperTrait;

    public function __construct()
    {
        parent::__construct(new User());
    }

    /**
     * Show the reset password form.
     */
    public function showResetForm(Request $request)
    {
        return view('auth.passwords.reset', [
            'token' => $request->token,
            'email' => $request->email,
        ]);
    }

    /**
     * Reset the given user's password.
     */
    public function reset(Request $request)
    {
        $validated = $this->validateRequestData($request, [
            'token' => 'required',
            'email' => 'required|email',
            'password' => [
                'required',
                'confirmed',
                'min:8',
                new ZxcvbnRule([
                    $request->email,
                ]),
            ],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                Auth::login($user);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            LoggingUtil::info('Password reset successful', [
                'email' => $validated['email'],
                'user_id' => Auth::id(),
            ], 'auth');

            return redirect()
                ->route('game.dashboard')
                ->with('success', 'Your password has been reset successfully.');
        }

        LoggingUtil::warning('Password reset failed', [
            'email' => $validated['email'],
            'status' => $status,
        ], 'auth');

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
