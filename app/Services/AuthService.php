<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Exceptions\ApiException;
use App\Models\SchoolProfile;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Encapsulates the platform's authentication flows (token issuance,
 * session management, registration and password recovery).
 */
class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly AuthFactory $auth,
        private readonly Hasher $hasher,
        private readonly TenantService $tenants,
    ) {}

    /**
     * Authenticate credentials and issue a fresh personal access token.
     *
     * @return array{token: string, user: User}
     */
    public function attemptLogin(string $email, string $password, string $tokenName = 'web', ?array $abilities = null): array
    {
        $user = $this->users->findBy(['email' => $email]);

        if (! $user instanceof User || ! $this->hasher->check($password, $user->password)) {
            throw ApiException::unauthorized('The provided credentials are incorrect.');
        }

        if (! $user->is_active) {
            throw ApiException::forbidden('Your account has been deactivated. Contact your administrator.');
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken($tokenName, $abilities ?? ['*']);

        return [
            'token' => $token->plainTextToken,
            'user' => $user->load(['roles:id,name,label,description,guard_name', 'schoolProfile:id,name,short_name']),
        ];
    }

    /**
     * Create a new user account and issue a token.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{token: string, user: User}
     */
    public function register(array $attributes, string $tokenName = 'web'): array
    {
        $user = $this->users->create([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'password' => $attributes['password'],
            'is_active' => true,
        ]);

        $token = $user->createToken($tokenName);

        return [
            'token' => $token->plainTextToken,
            'user' => $user->load(['roles:id,name,label,description,guard_name', 'schoolProfile:id,name,short_name']),
        ];
    }

    /**
     * Register a school together with its administrator account.
     *
     * The school profile is created first, its billing tenant is derived from
     * it, and the registering administrator is anchored to the school as a
     * school administrator.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{token: string, user: User}
     */
    public function registerSchool(array $attributes, string $tokenName = 'web'): array
    {
        $school = DB::transaction(function () use ($attributes): SchoolProfile {
            $school = SchoolProfile::query()->create([
                'name' => $attributes['school_name'],
                'short_name' => $attributes['short_name'] ?? null,
                'school_id' => $attributes['school_id'] ?? null,
                'region' => $attributes['region'] ?? null,
                'division' => $attributes['division'] ?? null,
                'district' => $attributes['district'] ?? null,
                'address' => $attributes['address'] ?? null,
                'contact_number' => $attributes['contact_number'] ?? null,
                'email' => $attributes['school_email'] ?? null,
                'website' => $attributes['website'] ?? null,
            ]);

            $this->tenants->resolveForSchool($school);

            return $school;
        });

        $user = $this->users->create([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'password' => $attributes['password'],
            'is_active' => true,
            'school_profile_id' => $school->id,
        ]);

        $user->assignRole(RoleEnum::SCHOOL_ADMINISTRATOR->roleName());

        $token = $user->createToken($tokenName);

        return [
            'token' => $token->plainTextToken,
            'user' => $user->load(['roles:id,name,label,description,guard_name', 'schoolProfile:id,name,short_name']),
        ];
    }

    /**
     * Revoke the token used for the current request.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    /**
     * Revoke a specific token/session belonging to the user.
     */
    public function revokeToken(User $user, int $tokenId): void
    {
        $token = $user->tokens()->whereKey($tokenId)->first();

        if (! $token) {
            throw ApiException::notFound('Session not found.');
        }

        if ($user->currentAccessToken()?->getKey() === $tokenId) {
            throw ApiException::badRequest('You cannot revoke the session you are currently using here.');
        }

        $token->delete();
    }

    /**
     * Revoke every token/session belonging to the user.
     */
    public function revokeAllTokens(User $user, ?int $exceptTokenId = null): int
    {
        $query = $user->tokens();

        if ($exceptTokenId !== null) {
            $query->whereKeyNot($exceptTokenId);
        }

        return $query->delete();
    }

    /**
     * All active sessions (personal access tokens) for the user.
     *
     * @return Collection<int, PersonalAccessToken>
     */
    public function sessions(User $user): Collection
    {
        return $user->tokens()->orderByDesc('last_used_at')->get();
    }

    /**
     * Send a password reset link to the given email address.
     *
     * @return string One of the Password::RESET_* constants
     */
    public function sendPasswordResetLink(string $email): string
    {
        return $this->passwordBroker()->sendResetLink(['email' => $email]);
    }

    /**
     * Reset the password using the given token.
     *
     * @param  array<string, string>  $credentials
     * @return string One of the Password::RESET_* constants
     */
    public function resetPassword(array $credentials): string
    {
        return $this->passwordBroker()->reset(
            $credentials,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $this->hasher->make($password),
                    'remember_token' => null,
                ])->save();
            }
        );
    }

    /**
     * Update the authenticated user's profile details.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateProfile(User $user, array $attributes): User
    {
        $user->forceFill([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
        ])->save();

        return $user->load(['roles:id,name,label,description,guard_name', 'schoolProfile:id,name,short_name']);
    }

    /**
     * Verify and update the authenticated user's password.
     *
     * @param  array<string, string>  $attributes
     */
    public function changePassword(User $user, array $attributes): void
    {
        $user->forceFill([
            'password' => $this->hasher->make($attributes['password']),
            'remember_token' => null,
        ])->save();
    }

    /**
     * Delete the authenticated user's account after confirming their password.
     *
     * @param  array<string, string>  $attributes
     */
    public function deleteAccount(User $user, array $attributes): void
    {
        if (! $this->hasher->check($attributes['password'], $user->password)) {
            throw ApiException::badRequest('The provided password does not match your account.', [
                'password' => ['The provided password does not match your account.'],
            ]);
        }

        $user->delete();
    }

    private function passwordBroker(): PasswordBroker
    {
        return Password::broker();
    }
}
