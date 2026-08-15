<?php

namespace Tests\Feature\Modules;

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Building;
use App\Models\Campus;
use App\Models\GradeLevel;
use App\Models\MasterData;
use App\Models\Room;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUPER_ADMINISTRATOR->roleName());

        return $user;
    }

    public function test_module_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/school-profiles')->assertStatus(401);
        $this->getJson('/api/v1/academic-years')->assertStatus(401);
        $this->getJson('/api/v1/master-data')->assertStatus(401);
    }

    public function test_module_index_returns_the_paginated_envelope(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $response = $this->getJson('/api/v1/grade-levels');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'items' => [],
                    'pagination' => ['current_page', 'per_page', 'total', 'last_page'],
                ],
            ]);
    }

    public function test_search_filters_and_sorting_work_on_lists(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        GradeLevel::factory()->create(['name' => 'Grade 7', 'code' => '7', 'sequence' => 7]);

        $this->getJson('/api/v1/grade-levels?search=Grade&sort_by=sequence&sort_dir=desc&per_page=5')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/grade-levels?filter[is_active]=true')
            ->assertOk();
    }

    public function test_full_crud_lifecycle_with_restore(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        // Create
        $store = $this->postJson('/api/v1/buildings', [
            'name' => 'Test Building',
            'code' => 'BLD-TEST',
            'is_active' => true,
        ])->assertCreated()->assertJsonPath('success', true);

        $id = $store->json('data.id');

        // Show
        $this->getJson("/api/v1/buildings/{$id}")->assertOk();

        // Update
        $this->putJson("/api/v1/buildings/{$id}", ['name' => 'Renamed Building', 'code' => 'BLD-TEST'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed Building');

        // Soft delete
        $this->deleteJson("/api/v1/buildings/{$id}")->assertOk();
        $this->assertSoftDeleted('buildings', ['id' => $id]);

        // Trashed listing exposes soft-deleted records
        $this->getJson('/api/v1/buildings?trashed=true')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $id);

        // Restore
        $this->postJson("/api/v1/buildings/{$id}/restore")->assertOk();
        $this->assertDatabaseHas('buildings', ['id' => $id, 'deleted_at' => null]);

        // Delete then force delete
        $this->deleteJson("/api/v1/buildings/{$id}")->assertOk();
        $this->deleteJson("/api/v1/buildings/{$id}/force")->assertOk();
        $this->assertDatabaseMissing('buildings', ['id' => $id]);
    }

    public function test_store_validates_the_payload(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $this->postJson('/api/v1/subjects', ['name' => 'No Code'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['code']]);
    }

    public function test_only_one_active_academic_year_is_allowed(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $first = $this->postJson('/api/v1/academic-years', [
            'name' => '2025-2026',
            'start_date' => '2025-06-01',
            'end_date' => '2026-03-31',
            'calendar_type' => 'quarterly',
            'is_active' => true,
        ])->assertCreated()->json('data.id');

        $this->postJson('/api/v1/academic-years', [
            'name' => '2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-03-31',
            'calendar_type' => 'quarterly',
            'is_active' => true,
        ])->assertCreated();

        $this->assertFalse((bool) AcademicYear::find($first)->is_active);
    }

    public function test_academic_terms_cannot_overlap(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $year = AcademicYear::factory()->create(['calendar_type' => 'custom']);

        $this->postJson('/api/v1/academic-terms', [
            'academic_year_id' => $year->id,
            'name' => 'First',
            'sequence' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
        ])->assertCreated();

        $this->postJson('/api/v1/academic-terms', [
            'academic_year_id' => $year->id,
            'name' => 'Second',
            'sequence' => 2,
            'start_date' => '2026-05-01',
            'end_date' => '2026-12-31',
        ])->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_term_count_must_match_the_calendar_type(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $year = AcademicYear::factory()->create(['calendar_type' => 'semester']);

        $this->postJson('/api/v1/academic-terms', [
            'academic_year_id' => $year->id,
            'name' => 'First Semester',
            'sequence' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
        ])->assertCreated();

        $this->postJson('/api/v1/academic-terms', [
            'academic_year_id' => $year->id,
            'name' => 'Second Semester',
            'sequence' => 2,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ])->assertCreated();

        $this->postJson('/api/v1/academic-terms', [
            'academic_year_id' => $year->id,
            'name' => 'Third',
            'sequence' => 3,
            'start_date' => '2027-01-01',
            'end_date' => '2027-03-31',
        ])->assertStatus(422);
    }

    public function test_system_master_data_entries_cannot_be_deleted(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $entry = MasterData::factory()->create(['is_system' => true, 'type' => 'religion']);

        $this->deleteJson("/api/v1/master-data/{$entry->id}")
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('master_data', ['id' => $entry->id, 'deleted_at' => null]);
    }

    public function test_school_administrator_can_manage_foundation_modules(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SCHOOL_ADMINISTRATOR->roleName());

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/departments')
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/campuses', ['name' => 'Annex Campus', 'is_active' => true])
            ->assertCreated();
    }

    public function test_plain_user_cannot_access_foundation_modules(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/sections')
            ->assertStatus(403);
    }

    public function test_seed_provides_the_default_school_framework(): void
    {
        $this->seed();

        $this->assertDatabaseHas('school_profiles', ['name' => 'Baguio Patriotic High School', 'is_active' => true]);
        $this->assertDatabaseHas('academic_years', ['name' => '2026-2027', 'is_active' => true]);
        $this->assertSame(4, AcademicYear::where('name', '2026-2027')->first()->terms()->count());
        $this->assertSame(12, GradeLevel::count());
        $this->assertGreaterThan(0, Section::count());
        $this->assertGreaterThan(0, Subject::count());
        $this->assertGreaterThan(0, Campus::count());
        $this->assertGreaterThan(0, Building::count());
        $this->assertGreaterThan(0, Room::count());
        $this->assertGreaterThan(0, MasterData::count());
    }
}
