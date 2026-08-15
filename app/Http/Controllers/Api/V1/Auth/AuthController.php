<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\AuthResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends ApiController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Authenticate a user and issue a bearer token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->attemptLogin(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            tokenName: $request->string('device_name', 'web')->toString(),
        );

        return $this->success(
            new AuthResource($result),
            'Logged in successfully.',
        );
    }

    /**
     * Register a new school and its administrator account.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->registerSchool($request->validated());

        return $this->success(
            new AuthResource($result),
            'School registered successfully.',
            201,
        );
    }

    /**
     * Return the currently authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roles:id,name,label,description,guard_name', 'schoolProfile:id,name,short_name', 'activeCampus:id,name,code']);

        return $this->success(
            new UserResource($user),
            'Authenticated user retrieved.',
        );
    }

    /**
     * Log the user out by revoking the current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->success(null, 'Logged out successfully.');
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->authService->updateProfile($request->user(), $request->validated());

        return $this->success(
            new UserResource($user),
            'Profile updated successfully.',
        );
    }

    /**
     * Update the authenticated user's password.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->authService->changePassword($request->user(), $request->validated());

        return $this->success(null, 'Password updated successfully.');
    }

    /**
     * Delete the authenticated user's account.
     */
    public function destroy(Request $request): JsonResponse
    {
        $this->authService->deleteAccount($request->user(), $request->validate([
            'password' => ['required', 'string'],
        ]));

        return $this->success(null, 'Account deleted successfully.');
    }
}
