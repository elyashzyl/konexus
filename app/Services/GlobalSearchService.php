<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Employee;
use App\Models\Enrollment;
use App\Models\ParentGuardian;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * The global (spotlight) search used by the navigation bar.
 *
 * Part 8 – Global Search. Results are grouped per entity and only include the
 * entities the authenticated user is allowed to access. A small set of top
 * matches is returned with an optional "view all" pointer per group.
 */
class GlobalSearchService
{
    /**
     * Run the search across the accessible entities of the user.
     *
     * @return array<string, mixed>
     */
    public function search(?\Illuminate\Contracts\Auth\Authenticatable $user, string $term): array
    {
        $term = trim($term);

        if ($term === '') {
            return $this->emptyPayload();
        }

        $groups = [];

        if ($this->can($user, 'students')) {
            $groups['students'] = $this->shapeStudents(
                Student::query()
                    ->where(fn ($q) => $q->where('first_name', 'like', "%{$term}%")->orWhere('middle_name', 'like', "%{$term}%")->orWhere('last_name', 'like', "%{$term}%")->orWhere('student_number', 'like', "%{$term}%")->orWhere('lrn', 'like', "%{$term}%"))
                    ->limit(5)
                    ->get()
            );
        }

        if ($this->can($user, 'parents')) {
            $groups['parents'] = $this->shapeParents(
                ParentGuardian::query()
                    ->where(fn ($q) => $q->where('first_name', 'like', "%{$term}%")->orWhere('last_name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"))
                    ->limit(5)
                    ->get()
            );
        }

        if ($this->can($user, 'employees') || $this->can($user, 'teachers') || $this->can($user, 'staff')) {
            $groups['people'] = $this->shapePeople(
                Employee::query()
                    ->where(fn ($q) => $q->where('first_name', 'like', "%{$term}%")->orWhere('middle_name', 'like', "%{$term}%")->orWhere('last_name', 'like', "%{$term}%")->orWhere('employee_number', 'like', "%{$term}%"))
                    ->limit(5)
                    ->get()
            );
        }

        if ($this->can($user, 'enrollments')) {
            $groups['enrollments'] = $this->shapeEnrollments(
                Enrollment::query()
                    ->with(['student', 'academicYear'])
                    ->where(function ($q) use ($term): void {
                        $q->where('enrollment_number', 'like', "%{$term}%")
                            ->orWhere('reference_number', 'like', "%{$term}%")
                            ->orWhereHas('student', fn ($q) => $q->where('first_name', 'like', "%{$term}%")->orWhere('last_name', 'like', "%{$term}%")->orWhere('lrn', 'like', "%{$term}%"));
                    })
                    ->limit(5)
                    ->get()
            );
        }

        if ($this->can($user, 'announcements')) {
            $groups['announcements'] = $this->shapeAnnouncements(
                Announcement::query()
                    ->where('title', 'like', "%{$term}%")
                    ->orWhere('content', 'like', "%{$term}%")
                    ->limit(5)
                    ->get()
            );
        }

        if ($this->can($user, 'sections')) {
            $groups['sections'] = $this->shapeSections(
                Section::query()->with('gradeLevel')
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%")
                    ->limit(5)
                    ->get()
            );
        }

        if ($this->can($user, 'subjects')) {
            $groups['subjects'] = $this->shapeSubjects(
                Subject::query()
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%")
                    ->limit(5)
                    ->get()
            );
        }

        $total = array_sum(array_map('count', $groups));

        return [
            'term' => $term,
            'total' => $total,
            'groups' => $groups,
        ];
    }

    /**
     * The empty search payload.
     *
     * @return array<string, mixed>
     */
    protected function emptyPayload(): array
    {
        return ['term' => '', 'total' => 0, 'groups' => []];
    }

    /**
     * Whether the user may access an entity group.
     */
    protected function can(?\Illuminate\Contracts\Auth\Authenticatable $user, string $module): bool
    {
        if ($user === null) {
            return false;
        }

        return Gate::forUser($user)->allows('view-module', $module);
    }

    /**
     * @param  Collection<int, Student>  $students
     * @return Collection<int, array<string, mixed>>
     */
    protected function shapeStudents(Collection $students): Collection
    {
        return $students->map(fn (Student $student) => [
            'id' => $student->id,
            'label' => $student->full_name,
            'subtitle' => $student->student_number ?: ($student->lrn ? 'LRN '.$student->lrn : 'Student'),
            'route' => ['name' => 'students.show', 'params' => ['id' => $student->id]],
        ]);
    }

    /**
     * @param  Collection<int, ParentGuardian>  $parents
     * @return Collection<int, array<string, mixed>>
     */
    protected function shapeParents(Collection $parents): Collection
    {
        return $parents->map(fn (ParentGuardian $parent) => [
            'id' => $parent->id,
            'label' => $parent->full_name,
            'subtitle' => $parent->relationship_to_student ?: 'Parent',
            'route' => ['name' => 'parents.show', 'params' => ['id' => $parent->id]],
        ]);
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @return Collection<int, array<string, mixed>>
     */
    protected function shapePeople(Collection $employees): Collection
    {
        return $employees->map(fn (Employee $employee) => [
            'id' => $employee->id,
            'label' => $employee->full_name,
            'subtitle' => $employee->employee_number ?: 'Employee',
            'route' => ['name' => 'employees.show', 'params' => ['id' => $employee->id]],
        ]);
    }

    /**
     * @param  Collection<int, Enrollment>  $enrollments
     * @return Collection<int, array<string, mixed>>
     */
    protected function shapeEnrollments(Collection $enrollments): Collection
    {
        return $enrollments->map(fn (Enrollment $enrollment) => [
            'id' => $enrollment->id,
            'label' => $enrollment->enrollment_number ?: '#'.$enrollment->id,
            'subtitle' => trim(implode(' ', array_filter([
                $enrollment->student?->full_name,
                $enrollment->academicYear?->name,
            ]))) ?: 'Enrollment',
            'route' => ['name' => 'enrollments.show', 'params' => ['id' => $enrollment->id]],
        ]);
    }

    /**
     * @param  Collection<int, Announcement>  $announcements
     * @return Collection<int, array<string, mixed>>
     */
    protected function shapeAnnouncements(Collection $announcements): Collection
    {
        return $announcements->map(fn (Announcement $announcement) => [
            'id' => $announcement->id,
            'label' => $announcement->title,
            'subtitle' => $announcement->category ?? 'Announcement',
            'route' => ['name' => 'announcements.show', 'params' => ['id' => $announcement->id]],
        ]);
    }

    /**
     * @param  Collection<int, Section>  $sections
     * @return Collection<int, array<string, mixed>>
     */
    protected function shapeSections(Collection $sections): Collection
    {
        return $sections->map(fn (Section $section) => [
            'id' => $section->id,
            'label' => $section->name,
            'subtitle' => $section->code ?: ($section->gradeLevel?->name ?? 'Section'),
            'route' => ['name' => 'sections.show', 'params' => ['id' => $section->id]],
        ]);
    }

    /**
     * @param  Collection<int, Subject>  $subjects
     * @return Collection<int, array<string, mixed>>
     */
    protected function shapeSubjects(Collection $subjects): Collection
    {
        return $subjects->map(fn (Subject $subject) => [
            'id' => $subject->id,
            'label' => $subject->name,
            'subtitle' => $subject->code ?: 'Subject',
            'route' => ['name' => 'subjects.show', 'params' => ['id' => $subject->id]],
        ]);
    }
}