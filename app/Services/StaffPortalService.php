<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Building;
use App\Models\Campus;
use App\Models\Employee;
use App\Models\Enrollment;
use App\Models\Room;
use App\Models\SchoolCalendarEvent;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Aggregated, role-scoped statistics for the staff portals.
 *
 * Every staff role receives a small set of counters relevant to its office
 * (enrollment queues for the registrar, approvals for the principal,
 * collections for finance, reference volumes for support offices). Counts are
 * computed school-wide and are safe to expose to authenticated staff.
 */
class StaffPortalService
{
    /**
     * The dashboard payload for an authenticated staff member.
     *
     * @return array<string, mixed>
     */
    public function dashboard(User $user): array
    {
        $role = $this->staffRole($user);

        return [
            'role' => $role,
            'academic_year' => AcademicYear::query()->where('is_active', true)->orderByDesc('start_date')->first()?->name,
            'campus' => $this->campusName($user),
            'unread_notifications' => DatabaseNotification::query()
                ->whereMorphedTo('notifiable', $user)
                ->whereNull('read_at')
                ->count(),
            'stats' => $this->statsFor($role),
        ];
    }

    /**
     * The primary staff role of the user (null for non-staff accounts).
     */
    private function staffRole(User $user): ?string
    {
        foreach (['registrar', 'principal', 'finance-officer', 'guidance-counselor', 'school-nurse', 'librarian', 'hr-officer', 'inventory-officer'] as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }

        return null;
    }

    private function campusName(User $user): ?string
    {
        return Campus::query()->find($user->active_campus_id)?->name
            ?? Campus::query()->orderBy('id')->first()?->name;
    }

    /**
     * Role-scoped statistics.
     *
     * Each stat is: key, label, value, href (portal route suffix or null).
     *
     * @return list<array{key: string, label: string, value: int, href: string|null}>
     */
    private function statsFor(?string $role): array
    {
        return match ($role) {
            'registrar' => [
                ['key' => 'enrollments', 'label' => 'Enrollment records', 'value' => Enrollment::query()->count(), 'href' => 'enrollments'],
                ['key' => 'officially-enrolled', 'label' => 'Officially enrolled', 'value' => $this->statusCount(EnrollmentStatus::OFFICIALLY_ENROLLED), 'href' => 'enrollments'],
                ['key' => 'for-review', 'label' => 'Awaiting registrar review', 'value' => $this->statusCount(EnrollmentStatus::FOR_REGISTRAR_REVIEW), 'href' => 'enrollment-operations'],
                ['key' => 'drafts', 'label' => 'Draft applications', 'value' => $this->statusCount(EnrollmentStatus::DRAFT), 'href' => 'enrollment-operations'],
                ['key' => 'students', 'label' => 'Students on file', 'value' => $this->students(), 'href' => 'students'],
            ],
            'principal' => [
                ['key' => 'approvals', 'label' => 'Awaiting approval', 'value' => $this->statusCount(EnrollmentStatus::FOR_PRINCIPAL_APPROVAL), 'href' => 'enrollment-approvals'],
                ['key' => 'officially-enrolled', 'label' => 'Officially enrolled', 'value' => $this->statusCount(EnrollmentStatus::OFFICIALLY_ENROLLED), 'href' => null],
                ['key' => 'students', 'label' => 'Students on file', 'value' => $this->students(), 'href' => null],
                ['key' => 'announcements', 'label' => 'Published announcements', 'value' => $this->announcements(), 'href' => 'announcements'],
            ],
            'finance-officer' => [
                ['key' => 'payments', 'label' => 'Awaiting payment', 'value' => $this->statusCount(EnrollmentStatus::FOR_PAYMENT), 'href' => 'enrollment-payments'],
                ['key' => 'officially-enrolled', 'label' => 'Officially enrolled', 'value' => $this->statusCount(EnrollmentStatus::OFFICIALLY_ENROLLED), 'href' => null],
                ['key' => 'students', 'label' => 'Students on file', 'value' => $this->students(), 'href' => null],
                ['key' => 'announcements', 'label' => 'Published announcements', 'value' => $this->announcements(), 'href' => 'announcements'],
            ],
            default => [
                ['key' => 'students', 'label' => 'Students on file', 'value' => $this->students(), 'href' => null],
                ...match ($role) {
                    'hr-officer' => [['key' => 'employees', 'label' => 'Employees', 'value' => Employee::query()->count(), 'href' => null]],
                    'librarian' => [['key' => 'subjects', 'label' => 'Subjects offered', 'value' => Subject::query()->count(), 'href' => null]],
                    'inventory-officer' => [
                        ['key' => 'buildings', 'label' => 'Buildings', 'value' => Building::query()->count(), 'href' => null],
                        ['key' => 'rooms', 'label' => 'Rooms', 'value' => Room::query()->count(), 'href' => null],
                    ],
                    default => [],
                },
                ['key' => 'announcements', 'label' => 'Published announcements', 'value' => $this->announcements(), 'href' => 'announcements'],
                ['key' => 'events', 'label' => 'Upcoming calendar events', 'value' => $this->upcomingEvents(), 'href' => 'calendar'],
            ],
        };
    }

    private function statusCount(EnrollmentStatus $status): int
    {
        return Enrollment::query()->where('status', $status->value)->count();
    }

    private function students(): int
    {
        return Student::query()->where('is_active', true)->count();
    }

    private function announcements(): int
    {
        return Announcement::query()->where('published', true)->count();
    }

    private function upcomingEvents(): int
    {
        return SchoolCalendarEvent::query()
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereDate('start_date', '>=', today())->orWhereDate('end_date', '>=', today()))
            ->count();
    }
}
