<?php

namespace Tests\Feature\Modules;

use App\Enums\RoleEnum;
use App\Models\Announcement;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class PlatformModuleTest extends TestCase
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

    private function schoolAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SCHOOL_ADMINISTRATOR->roleName());

        return $user;
    }

    private function studentUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::STUDENT->roleName());

        return $user;
    }

    // ─────────────────────────────────────────
    // Notification Center
    // ─────────────────────────────────────────

    public function test_notification_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/notifications')->assertStatus(401);
        $this->getJson('/api/v1/notifications/unread-count')->assertStatus(401);
        $this->getJson('/api/v1/notification-preferences')->assertStatus(401);
        $this->putJson('/api/v1/notification-preferences', ['matrix' => []])->assertStatus(401);
    }

    public function test_user_can_read_and_update_notification_preferences(): void
    {
        $user = $this->studentUser();

        $this->actingAs($user)
            ->getJson('/api/v1/notification-preferences')
            ->assertOk()
            ->assertJsonStructure(['data' => ['categories', 'channels', 'matrix']]);

        $this->actingAs($user)
            ->putJson('/api/v1/notification-preferences', [
                'matrix' => ['enrollment' => ['database' => false, 'email' => true]],
            ])
            ->assertOk()
            ->assertJsonPath('data.matrix.enrollment.database', false)
            ->assertJsonPath('data.matrix.enrollment.email', true);
    }

    public function test_notifications_list_and_acknowledgement(): void
    {
        $user = $this->studentUser();
        $user->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'database',
            'data' => json_encode(['type' => 'system', 'title' => 'Hi', 'body' => 'There']),
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1);

        $this->actingAs($user)
            ->getJson('/api/v1/notifications/unread-count')
            ->assertJsonPath('data.unread', 1);

        $id = DatabaseNotification::query()->where('notifiable_id', $user->id)->first()->getKey();

        $this->actingAs($user)
            ->patchJson("/api/v1/notifications/{$id}/read")
            ->assertOk()
            ->assertJsonStructure(['data' => ['read_at', 'created_at']]);

        $this->actingAs($user)
            ->patchJson('/api/v1/notifications/read-all')
            ->assertOk();
    }

    // ─────────────────────────────────────────
    // Global Search
    // ─────────────────────────────────────────

    public function test_global_search_requires_authentication(): void
    {
        $this->getJson('/api/v1/search?q=Maria')->assertStatus(401);
    }

    public function test_global_search_returns_scoped_groups(): void
    {
        $admin = $this->superAdmin();
        $student = Student::factory()->create(['first_name' => 'Maria', 'last_name' => 'Santos']);

        $this->actingAs($admin)
            ->getJson('/api/v1/search?q=Maria')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.groups.students.0.id', $student->id)
            ->assertJsonPath('data.groups.students.0.route.params.id', $student->id);
    }

    // ─────────────────────────────────────────
    // Announcement targeting
    // ─────────────────────────────────────────

    public function test_announcement_with_audience_and_scheduling(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/announcements', [
                'title' => 'Summer Class',
                'content' => 'Registration opens next week.',
                'status' => 'scheduled',
                'scheduled_at' => now()->addDay()->toDateTimeString(),
                'audience' => ['roles' => ['student'], 'grade_level_ids' => [1]],
            ])
            ->assertCreated();

        $id = $response->json('data.id');

        $announcement = Announcement::query()->find($id);
        $this->assertEquals('scheduled', $announcement->status);
        $this->assertIsArray($announcement->audience);
        $this->assertEquals($admin->id, $announcement->created_by);
    }

    public function test_announcement_mine_feed_respects_audience(): void
    {
        $this->actingAs($this->superAdmin())->postJson('/api/v1/announcements', [
            'title' => 'For Students Only',
            'content' => 'Exclusive.',
            'published' => true,
            'audience' => ['roles' => ['student']],
        ]);

        $this->actingAs($this->superAdmin())->postJson('/api/v1/announcements', [
            'title' => 'For Everyone',
            'content' => 'All.',
            'published' => true,
            'audience' => ['roles' => ['everyone']],
        ]);

        $studentUser = $this->studentUser();

        $this->actingAs($studentUser)
            ->getJson('/api/v1/announcements/mine')
            ->assertOk()
            ->assertJsonFragment(['title' => 'For Students Only'])
            ->assertJsonFragment(['title' => 'For Everyone']);
    }

    // ─────────────────────────────────────────
    // Audit & Activity Center
    // ─────────────────────────────────────────

    public function test_activity_logs_restricted_to_operators(): void
    {
        $student = $this->studentUser();

        $this->actingAs($student)->getJson('/api/v1/activity-logs')->assertStatus(403);
    }

    public function test_activity_logs_available_to_super_admin(): void
    {
        $admin = $this->superAdmin();
        $student = Student::factory()->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/activity-logs')
            ->assertOk()
            ->assertJsonStructure(['data' => ['items', 'pagination']]);

        $this->actingAs($admin)
            ->getJson('/api/v1/activity-logs/stats')
            ->assertOk()
            ->assertJsonStructure(['data' => ['total', 'today']]);
    }

    // ─────────────────────────────────────────
    // User Management
    // ─────────────────────────────────────────

    public function test_super_admin_can_manage_users(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/users', [
                'name' => 'Jane Registrar',
                'email' => 'jane@example.com',
                'password' => 'secret123',
                'roles' => ['registrar'],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
        $this->assertDatabaseHas('model_has_roles', [
            'model_id' => $response->json('data.id'),
            'role_id' => Role::query()->where('name', 'registrar')->first()->id,
        ]);
    }

    public function test_school_admin_cannot_delete_super_admin(): void
    {
        $target = $this->superAdmin();
        $operator = $this->schoolAdmin();

        $this->actingAs($operator)
            ->deleteJson("/api/v1/users/{$target->id}")
            ->assertForbidden();
    }

    public function test_user_toggle_active_and_reset_password(): void
    {
        $admin = $this->superAdmin();
        $target = $this->studentUser();

        $this->actingAs($admin)
            ->patchJson("/api/v1/users/{$target->id}/active")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->actingAs($admin)
            ->postJson("/api/v1/users/{$target->id}/reset-password", ['password' => 'newsecret1'])
            ->assertOk();
    }

    // ─────────────────────────────────────────
    // System Settings (grouped)
    // ─────────────────────────────────────────

    public function test_grouped_settings_update(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->getJson('/api/v1/system-settings/grouped')
            ->assertOk()
            ->assertJsonStructure(['data' => ['groups']]);

        $this->actingAs($admin)
            ->putJson('/api/v1/system-settings/grouped', [
                'settings' => ['school_name' => 'KONEXUS Academy', 'sms_enabled' => 'true'],
            ])
            ->assertOk()
            ->assertJsonPath('data.updated.school_name', 'KONEXUS Academy');

        $this->assertDatabaseHas('system_settings', ['key' => 'school_name', 'value' => 'KONEXUS Academy']);
    }

    public function test_grouped_settings_reject_unknown_key(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->putJson('/api/v1/system-settings/grouped', ['settings' => ['unknown_key' => 'x']])
            ->assertStatus(422);
    }

    // ─────────────────────────────────────────
    // Backups & System Health
    // ─────────────────────────────────────────

    public function test_backups_require_super_admin(): void
    {
        $this->actingAs($this->schoolAdmin())->getJson('/api/v1/backups')->assertStatus(403);
        $this->actingAs($this->schoolAdmin())->getJson('/api/v1/system-health')->assertStatus(403);
    }

    public function test_super_admin_can_create_backup(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->postJson('/api/v1/backups', ['notes' => 'pre-migration'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('backups', ['notes' => 'pre-migration']);

        $this->actingAs($admin)
            ->getJson('/api/v1/system-health')
            ->assertOk()
            ->assertJsonPath('data.database.connected', true);
    }

    // ─────────────────────────────────────────
    // Reports
    // ─────────────────────────────────────────

    public function test_reports_catalog_and_csv_generation(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->getJson('/api/v1/reports')
            ->assertOk()
            ->assertJsonStructure(['data' => ['items', 'context']]);

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/reports/generate', ['report' => 'students', 'format' => 'csv'])
            ->assertOk();

        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }

    public function test_reports_generate_pdf(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->postJson('/api/v1/reports/generate', ['report' => 'students', 'format' => 'pdf'])
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_reports_reject_unknown_report(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->postJson('/api/v1/reports/generate', ['report' => 'nope', 'format' => 'csv'])
            ->assertStatus(422);
    }

    // ─────────────────────────────────────────
    // Admin Dashboard
    // ─────────────────────────────────────────

    public function test_admin_dashboard_restricted_to_operators(): void
    {
        $this->actingAs($this->studentUser())->getJson('/api/v1/admin/dashboard')->assertStatus(403);
    }

    public function test_admin_dashboard_returns_snapshot(): void
    {
        Student::factory()->create();

        $this->actingAs($this->superAdmin())
            ->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure(['data' => ['counters', 'enrollment_status', 'enrollment_trend']])
            ->assertJsonPath('data.counters.students', 1);
    }
}
