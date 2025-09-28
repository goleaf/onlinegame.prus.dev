<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Traits\ValidationHelperTrait;
use App\Utilities\LoggingUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use LaraUtilX\Http\Controllers\CrudController;

class ForgotPasswordController extends CrudController
{
    use ValidationHelperTrait;

    public function __construct()
    {
        parent::__construct(new User());
    }

    /**
     * Show the forgot password form.
     */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * Send a reset link to the given user.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $validated = $this->validateRequestData($request, ['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            LoggingUtil::info('Password reset link sent', [
                'email' => $validated['email'],
            ], 'auth');

            return back()->with(['status' => __($status)]);
        }

        LoggingUtil::warning('Password reset link failed', [
            'email' => $validated['email'],
            'status' => $status,
        ], 'auth');

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
