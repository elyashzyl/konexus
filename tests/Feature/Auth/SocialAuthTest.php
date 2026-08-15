<?php

namespace Tests\Feature\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialUserContract;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Stub the Socialite driver used by both the redirect and callback flows.
     */
    private function stubDriver(string $provider, ?SocialUserContract $socialUser = null, ?string $targetUrl = null): void
    {
        $redirect = \Mockery::mock();
        $redirect->shouldReceive('getTargetUrl')->andReturn($targetUrl ?? 'https://accounts.google.com/o/oauth2/auth?...');

        $driver = \Mockery::mock();
        $driver->shouldReceive('stateless')->andReturnSelf();
        $driver->shouldReceive('redirect')->andReturn($redirect);

        if ($socialUser !== null) {
            $driver->shouldReceive('user')->andReturn($socialUser);
        }

        Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
    }

    /**
     * Build a fake provider user contract.
     */
    private function socialUser(array $attributes = []): SocialUserContract
    {
        $user = \Mockery::mock(SocialUserContract::class);
        $user->shouldReceive('getId')->andReturn($attributes['id'] ?? 'social-123');
        $user->shouldReceive('getNickname')->andReturn($attributes['nickname'] ?? null);
        $user->shouldReceive('getName')->andReturn($attributes['name'] ?? 'Jane Doe');
        $user->shouldReceive('getEmail')->andReturn(array_key_exists('email', $attributes) ? $attributes['email'] : 'jane@example.com');
        $user->shouldReceive('getAvatar')->andReturn($attributes['avatar'] ?? null);

        return $user;
    }

    public function test_redirect_returns_the_provider_authorization_url()
    {
        $this->stubDriver('google', targetUrl: 'https://accounts.google.com/o/oauth2/auth?client_id=xyz');

        $this->getJson('/api/v1/auth/google/redirect')
            ->assertOk()
            ->assertJsonPath('data.url', 'https://accounts.google.com/o/oauth2/auth?client_id=xyz');
    }

    public function test_redirect_rejects_unsupported_providers()
    {
        $this->getJson('/api/v1/auth/twitter/redirect')
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_redirect_carries_an_intended_destination()
    {
        $this->stubDriver('facebook');

        $this->getJson('/api/v1/auth/facebook/redirect?intended=/enrollment')
            ->assertOk()
            ->assertJsonPath('data.intended', '/enrollment');
    }

    public function test_redirect_ignores_external_intended_destinations()
    {
        $this->stubDriver('google');

        $response = $this->getJson('/api/v1/auth/google/redirect?intended=//evil.example.com')
            ->assertOk();

        $this->assertArrayNotHasKey('intended', $response->json('data'));
    }

    public function test_callback_creates_a_user_and_redirects_with_a_token()
    {
        $this->stubDriver('google', $this->socialUser([
            'id' => 'google-123',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]));

        $this->get('/api/v1/auth/google/callback')
            ->assertRedirect();

        $user = User::query()->where('email', 'jane@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->email_verified_at);

        $this->assertDatabaseHas('social_accounts', [
            'provider' => 'google',
            'provider_id' => 'google-123',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'social-google',
        ]);
    }

    public function test_callback_links_an_existing_user_by_email()
    {
        $existing = User::factory()->create(['email' => 'jane@example.com']);

        $this->stubDriver('google', $this->socialUser(['email' => 'jane@example.com', 'id' => 'google-456']));

        $this->get('/api/v1/auth/google/callback')->assertRedirect();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('social_accounts', [
            'provider' => 'google',
            'provider_id' => 'google-456',
            'user_id' => $existing->id,
        ]);
    }

    public function test_callback_reuses_an_existing_social_account()
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-123',
        ]);

        $this->stubDriver('google', $this->socialUser(['email' => 'jane@example.com', 'id' => 'google-123']));

        $this->get('/api/v1/auth/google/callback')->assertRedirect();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('social_accounts', 1);
    }

    public function test_callback_rejects_deactivated_accounts()
    {
        $user = User::factory()->create(['email' => 'jane@example.com', 'is_active' => false]);

        $this->stubDriver('google', $this->socialUser(['email' => 'jane@example.com', 'id' => 'google-123']));

        $this->get('/api/v1/auth/google/callback')
            ->assertRedirect()
            ->assertRedirectContains('social_error');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_callback_redirects_to_login_when_the_provider_flow_fails()
    {
        $driver = \Mockery::mock();
        $driver->shouldReceive('stateless')->andReturnSelf();
        $driver->shouldReceive('user')->andThrow(new \Exception('Provider declined the request.'));

        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $this->get('/api/v1/auth/google/callback')
            ->assertRedirect()
            ->assertRedirectContains('social_error');
    }

    public function test_callback_redirects_to_login_when_the_provider_has_no_email()
    {
        $this->stubDriver('google', $this->socialUser(['email' => null]));

        $this->get('/api/v1/auth/google/callback')
            ->assertRedirect()
            ->assertRedirectContains('social_error');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_callback_forwards_an_intended_destination()
    {
        $this->stubDriver('google', $this->socialUser(['email' => 'jane@example.com']));

        $this->get('/api/v1/auth/google/callback?intended=/enrollment')
            ->assertRedirect()
            ->assertRedirectContains('intended=%2Fenrollment');
    }

    public function test_callback_rejects_unsupported_providers()
    {
        $this->get('/api/v1/auth/twitter/callback')
            ->assertRedirect()
            ->assertRedirectContains('social_error');
    }
}