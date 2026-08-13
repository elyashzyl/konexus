<?php

namespace App\Http\Controllers\Api\V1\Portal;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Teacher;
use App\Services\PortalIdentityService;
use App\Services\TeacherPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The Teacher Portal.
 *
 * Part 8 – Portals. Only the sections, schedules, students and grade records
 * assigned to the authenticated teacher are exposed. Non-teacher accounts
 * receive a 404-style "no profile" response.
 */
class TeacherPortalController extends ApiController
{
    public function __construct(
        private readonly PortalIdentityService $identities,
        private readonly TeacherPortalService $portal,
    ) {}

    /**
     * The portal landing page for the teacher.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $teacher = $this->teacher($request);

        return $this->success($this->portal->dashboard($teacher), 'Teacher dashboard retrieved.');
    }

    /**
     * The teaching assignments of the teacher.
     */
    public function assignments(Request $request): JsonResponse
    {
        $teacher = $this->teacher($request);

        return $this->success(['items' => $this->portal->assignments($teacher)->values()], 'Assignments retrieved.');
    }

    /**
     * The weekly schedule of the teacher.
     */
    public function schedule(Request $request): JsonResponse
    {
        $teacher = $this->teacher($request);

        return $this->success(['items' => $this->portal->schedule($teacher)->values()], 'Schedule retrieved.');
    }

    /**
     * The advisory class of the teacher, if any.
     */
    public function advisoryClass(Request $request): JsonResponse
    {
        $teacher = $this->teacher($request);

        return $this->success(['class' => $this->portal->advisoryClass($teacher)], 'Advisory class retrieved.');
    }

    /**
     * The roster of a section taught by the teacher.
     */
    public function classRoster(Request $request, int $sectionId, ?int $subjectId = null): JsonResponse
    {
        $teacher = $this->teacher($request);

        $subjectId = $request->integer('subject_id', 0) ?: $subjectId;

        return $this->success($this->portal->classRoster($teacher, $sectionId, $subjectId), 'Class roster retrieved.');
    }

    /**
     * The students taught by the teacher.
     */
    public function students(Request $request): JsonResponse
    {
        $teacher = $this->teacher($request);

        return $this->success(['items' => $this->portal->students($teacher)->values()], 'Students retrieved.');
    }

    /**
     * Resolve the teacher profile of the authenticated user.
     */
    protected function teacher(Request $request): Teacher
    {
        $teacher = $this->identities->teacher($request->user());

        if ($teacher === null) {
            abort(404, 'No teacher profile is linked to this account.');
        }

        return $teacher;
    }
}