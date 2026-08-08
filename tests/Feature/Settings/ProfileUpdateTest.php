<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_information_can_be_updated()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/auth/me', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Test User')
            ->assertJsonPath('data.email', 'test@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/auth/me', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ])
            ->assertOk();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account()
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/auth/me', [
                'password' => 'password',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account()
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/auth/me', [
                'password' => 'wrong-password',
            ])
            ->assertStatus(400)
            ->assertJsonPath('success', false);

        $this->assertNotNull($user->fresh());
    }
}
