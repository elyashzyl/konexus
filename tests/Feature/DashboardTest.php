<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_spa_shell_is_rendered_for_the_root_path()
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('app');
    }

    public function test_the_spa_shell_is_rendered_for_deep_links()
    {
        $response = $this->get('/dashboard');

        $response->assertOk();
        $response->assertViewIs('app');
    }

    public function test_the_login_route_renders_the_spa_shell()
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertViewIs('app');
    }

    public function test_the_spa_shell_contains_the_mount_point()
    {
        $this->get('/')
            ->assertSee('id="app"', false);
    }

    public function test_roles_catalog_is_available_without_authentication()
    {
        $this->getJson('/api/v1/roles/catalog')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => [['key', 'label', 'description']]]);
    }

    public function test_roles_index_requires_authentication()
    {
        $this->getJson('/api/v1/roles')->assertStatus(401);

        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/roles')
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
