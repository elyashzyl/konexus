<?php

namespace Tests\Feature\Modules;

use App\Enums\RoleEnum;
use App\Models\Campus;
use App\Models\GradeLevel;
use App\Models\SchoolProfile;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogCampusScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_grade_levels_are_scoped_to_the_active_campus_workspace(): void
    {
        $school = SchoolProfile::factory()->create(['is_active' => true]);
        $campusA = Campus::factory()->create(['school_profile_id' => $school->id, 'name' => 'Alpha Campus', 'is_active' => true]);
        $campusB = Campus::factory()->create(['school_profile_id' => $school->id, 'name' => 'Beta Campus', 'is_active' => true]);
        $admin = User::factory()->create(['school_profile_id' => $school->id, 'active_campus_id' => $campusA->id]);
        $admin->assignRole(RoleEnum::SCHOOL_ADMINISTRATOR->roleName());

        GradeLevel::factory()->create(['school_profile_id' => $school->id, 'campus_id' => $campusA->id, 'name' => 'Grade 7 Alpha']);
        GradeLevel::factory()->create(['school_profile_id' => $school->id, 'campus_id' => $campusB->id, 'name' => 'Grade 7 Beta']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/grade-levels')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.name', 'Grade 7 Alpha');
    }

    public function test_enrollment_settings_are_editable_per_school(): void
    {
        $schoolA = SchoolProfile::factory()->create(['name' => 'Alpha School', 'is_active' => true]);
        $schoolB = SchoolProfile::factory()->create(['name' => 'Beta School', 'is_active' => true]);
        $admin = User::factory()->create(['school_profile_id' => $schoolA->id]);
        $admin->assignRole(RoleEnum::SCHOOL_ADMINISTRATOR->roleName());

        SystemSetting::query()->create([
            'school_profile_id' => $schoolA->id,
            'group' => 'enrollment',
            'key' => 'enrollment_number_format',
            'value' => 'ALPHA-{YEAR}-{SEQ:4}',
            'type' => 'string',
        ]);
        SystemSetting::query()->create([
            'school_profile_id' => $schoolB->id,
            'group' => 'enrollment',
            'key' => 'enrollment_number_format',
            'value' => 'BETA-{YEAR}-{SEQ:4}',
            'type' => 'string',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/system-settings/grouped')
            ->assertOk()
            ->assertJsonPath('data.school.id', $schoolA->id);

        $enrollmentGroup = collect($this->getJson('/api/v1/system-settings/grouped')->json('data.groups'))
            ->firstWhere('group', 'enrollment');

        $this->assertNotNull($enrollmentGroup);
        $this->assertSame(
            'ALPHA-{YEAR}-{SEQ:4}',
            collect($enrollmentGroup['settings'])->firstWhere('key', 'enrollment_number_format')['value']
        );
    }
}
