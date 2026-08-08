<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

class PasswordController extends ApiController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Send a password reset link to the given email address.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = $this->authService->sendPasswordResetLink($request->string('email')->toString());

        if ($status !== Password::RESET_LINK_SENT) {
            return $this->error(
                'Unable to send the password reset link.',
                ['email' => [__($status)]],
                400,
            );
        }

        return $this->success(null, 'If that email address is in our system, a password reset link has been sent.');
    }

    /**
     * Reset the password using a valid reset token.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = $this->authService->resetPassword($request->validated());

        if ($status !== Password::PASSWORD_RESET) {
            return $this->error(
                'Unable to reset your password.',
                ['token' => [__($status)]],
                400,
            );
        }

        return $this->success(null, 'Your password has been reset. You can now log in with your new password.');
    }
}
