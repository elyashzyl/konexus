<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialUser;
use Laravel\Socialite\Facades\Socialite;

/**
 * Google / Facebook OAuth sign-in for the public portal.
 *
 * The redirect endpoint returns the provider authorization URL as JSON so the
 * SPA can hand off to the provider. The callback endpoint exchanges the
 * authorization code, links-or-creates a user account, issues a Sanctum token
 * and bounces the browser to the SPA callback page with that token.
 */
class SocialAuthController extends ApiController
{
    /**
     * The providers this installation supports.
     *
     * @var list<string>
     */
    private const PROVIDERS = ['google', 'facebook'];

    /**
     * Build the provider authorization URL for the SPA to redirect to.
     */
    public function redirect(string $provider, Request $request): JsonResponse
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            return $this->error('Unsupported sign-in provider.', null, 422);
        }

        $intended = $this->sanitizeIntended($request->string('intended')->toString());

        $url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();

        $data = ['url' => $url];

        if ($intended !== null) {
            $data['intended'] = $intended;
        }

        return $this->success($data, 'Redirect prepared.');
    }

    /**
     * Exchange the provider code for a session and bounce back to the SPA.
     */
    public function callback(string $provider, Request $request): RedirectResponse
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            return $this->failureRedirect('Unsupported sign-in provider.');
        }

        try {
            $social = Socialite::driver($provider)->stateless()->user();
        } catch (\Throwable) {
            return $this->failureRedirect('Sign-in was cancelled or could not be completed.');
        }

        $email = $social->getEmail();

        if (! $email) {
            return $this->failureRedirect('Your provider account does not include an email address.');
        }

        $user = $this->resolveUser($provider, $social);

        if (! $user->is_active) {
            return $this->failureRedirect('Your account has been deactivated. Contact your administrator.');
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken('social-'.$provider)->plainTextToken;

        $query = ['token' => $token];

        $intended = $this->sanitizeIntended($request->string('intended')->toString());

        if ($intended !== null) {
            $query['intended'] = $intended;
        }

        return redirect()->away(url('/auth/social/callback').'?'.http_build_query($query));
    }

    /**
     * Find the user behind a social identity, creating the account when needed.
     */
    private function resolveUser(string $provider, SocialUser $social): User
    {
        $existing = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_id', (string) $social->getId())
            ->first();

        if ($existing) {
            $existing->forceFill([
                'provider_email' => $social->getEmail(),
                'name' => $social->getName(),
                'avatar' => $social->getAvatar(),
            ])->save();

            return $existing->user;
        }

        $user = User::query()->where('email', $social->getEmail())->first();

        if (! $user) {
            $user = User::query()->forceCreate([
                'name' => $social->getName() ?: ($social->getNickname() ?: 'User'),
                'email' => $social->getEmail(),
                'password' => Str::random(64),
                'avatar' => $social->getAvatar(),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        SocialAccount::query()->create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => (string) $social->getId(),
            'provider_email' => $social->getEmail(),
            'name' => $social->getName(),
            'avatar' => $social->getAvatar(),
        ]);

        return $user;
    }

    /**
     * Redirect the browser to the login page with an error flag.
     */
    private function failureRedirect(string $message): RedirectResponse
    {
        return redirect()->away(url('/auth/login').'?social_error='.urlencode($message));
    }

    /**
     * Accept only same-origin, absolute paths as a post-login destination.
     */
    private function sanitizeIntended(?string $intended): ?string
    {
        if ($intended === null || $intended === '') {
            return null;
        }

        if (str_starts_with($intended, '//') || str_starts_with($intended, '\\')) {
            return null;
        }

        if (str_starts_with($intended, '/') && ! str_contains($intended, "\0")) {
            return $intended;
        }

        return null;
    }
}