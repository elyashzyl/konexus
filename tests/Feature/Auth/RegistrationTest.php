<?php

namespace Tests\Feature\Auth;

use App\Models\SchoolProfile;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_new_school_can_register_and_receive_a_token()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'school_name' => 'Baguio Patriotic High School',
            'short_name' => 'BPHS',
            'school_id' => '301234',
            'region' => 'CAR',
            'division' => 'Baguio City',
            'district' => 'Baguio City East',
            'address' => '123 Session Road, Baguio City',
            'contact_number' => '+63 900 000 0000',
            'school_email' => 'bps@deped.gov.ph',
            'website' => 'https://bps.example.com',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['token', 'token_type', 'expires_in', 'user'],
                'errors',
            ])
            ->assertJsonPath('data.user.email', 'test@example.com')
            ->assertJsonPath('data.user.school_profile_id', fn (int $id) => $id > 0);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->assertDatabaseHas('school_profiles', [
            'name' => 'Baguio Patriotic High School',
            'short_name' => 'BPHS',
            'school_id' => '301234',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'school_profile_id' => $user->school_profile_id,
            'is_active' => true,
        ]);

        $this->assertTrue($user->hasRole('school-administrator'));

        $this->assertDatabaseHas('tenants', [
            'school_profile_id' => $user->school_profile_id,
            'code' => 'BPHS',
        ]);

        $this->assertSame(1, Tenant::query()->where('school_profile_id', $user->school_profile_id)->count());
    }

    public function test_registration_requires_school_details()
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('school_name');

        $this->assertDatabaseCount('school_profiles', 0);
        $this->assertDatabaseCount('tenants', 0);
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_registration_requires_a_confirmed_password()
    {
        $this->postJson('/api/v1/auth/register', [
            'school_name' => 'Baguio Patriotic High School',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'different-password',
        ])->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_registration_requires_a_unique_email()
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->postJson('/api/v1/auth/register', [
            'school_name' => 'Baguio Patriotic High School',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }
}