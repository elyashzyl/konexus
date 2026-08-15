<?php

namespace Tests\Feature\Modules;

use App\Enums\RoleEnum;
use App\Models\Campus;
use App\Models\SchoolProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function school(string $name): SchoolProfile
    {
        return SchoolProfile::factory()->create(['name' => $name, 'is_active' => true]);
    }

    private function schoolAdmin(SchoolProfile $school): User
    {
        $user = User::factory()->create(['school_profile_id' => $school->id]);
        $user->assignRole(RoleEnum::SCHOOL_ADMINISTRATOR->roleName());

        return $user;
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUPER_ADMINISTRATOR->roleName());

        return $user;
    }

    public function test_school_admin_only_sees_own_school_profile(): void
    {
        $schoolA = $this->school('Alpha School');
        $this->school('Beta School');
        $admin = $this->schoolAdmin($schoolA);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/school-profiles')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.id', $schoolA->id);
    }

    public function test_school_admin_cannot_read_another_schools_profile(): void
    {
        $schoolA = $this->school('Alpha School');
        $schoolB = $this->school('Beta School');
        $admin = $this->schoolAdmin($schoolA);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/school-profiles/{$schoolB->id}")
            ->assertNotFound();
    }

    public function test_school_admin_only_sees_own_schools_module_data(): void
    {
        $schoolA = $this->school('Alpha School');
        $schoolB = $this->school('Beta School');

        Campus::factory()->create(['school_profile_id' => $schoolA->id, 'name' => 'A Main']);
        Campus::factory()->create(['school_profile_id' => $schoolB->id, 'name' => 'B Main']);

        $admin = $this->schoolAdmin($schoolA);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/campuses')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.school_profile_id', $schoolA->id);
    }

    public function test_records_created_by_school_admin_are_anchored_to_their_school(): void
    {
        $schoolA = $this->school('Alpha School');
        $admin = $this->schoolAdmin($schoolA);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/campuses', ['name' => 'A Annex', 'is_active' => true])
            ->assertCreated();

        $this->assertDatabaseHas('campuses', ['name' => 'A Annex', 'school_profile_id' => $schoolA->id]);
    }

    public function test_platform_admin_sees_all_schools(): void
    {
        $this->school('Alpha School');
        $this->school('Beta School');

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->getJson('/api/v1/school-profiles')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 2);
    }

    public function test_school_admin_users_page_is_scoped_to_own_school(): void
    {
        $schoolA = $this->school('Alpha School');
        $schoolB = $this->school('Beta School');

        $admin = $this->schoolAdmin($schoolA);
        $this->schoolAdmin($schoolB);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1);
    }
}