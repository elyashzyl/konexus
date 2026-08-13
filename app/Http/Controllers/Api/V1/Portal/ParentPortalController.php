<?php

namespace App\Http\Controllers\Api\V1\Portal;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Student;
use App\Services\PortalDataService;
use App\Services\PortalIdentityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The Parent Portal.
 *
 * Part 8 – Portals. A parent account can only ever see its own linked
 * children. Every endpoint resolves the parent from the authenticated user
 * and rejects access to students that are not linked.
 */
class ParentPortalController extends ApiController
{
    public function __construct(
        private readonly PortalIdentityService $identities,
        private readonly PortalDataService $data,
    ) {}

    /**
     * The portal landing page: children, targeted announcements.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $parent = $this->identities->parent($request->user());

        $children = $parent?->students()->get() ?? collect();
        $signature = $this->identities->audienceSignature($request->user());

        return $this->success([
            'parent' => $parent ? [
                'name' => $parent->full_name,
                'email' => $parent->email,
                'contact_number' => $parent->contact_number,
            ] : null,
            'children' => $children->map(fn (Student $student) => $this->data->childSummary($student))->values(),
            'announcements' => $this->data->targetedAnnouncements($signature)->values(),
            'modules' => $this->data->moduleAvailability(),
        ], 'Parent dashboard retrieved.');
    }

    /**
     * The children linked to the parent account.
     */
    public function children(Request $request): JsonResponse
    {
        $parent = $this->identities->parent($request->user());

        if ($parent === null) {
            return $this->success([], 'No children linked to this account.');
        }

        return $this->success([
            'items' => $parent->students()->get()
                ->map(fn (Student $student) => $this->data->childSummary($student))
                ->values(),
        ], 'Children retrieved.');
    }

    /**
     * The full, permission-scoped profile of a linked child.
     */
    public function child(Request $request, int $id): JsonResponse
    {
        $student = $this->linkedChild($request, $id);

        return $this->success($this->data->childSummary($student), 'Child retrieved.');
    }

    /**
     * The class schedule of a linked child.
     */
    public function childSchedule(Request $request, int $id): JsonResponse
    {
        $student = $this->linkedChild($request, $id);

        return $this->success(['items' => $this->data->schedule($student)->values()], 'Child schedule retrieved.');
    }

    /**
     * The published grades / report card of a linked child.
     */
    public function childGrades(Request $request, int $id): JsonResponse
    {
        $student = $this->linkedChild($request, $id);

        return $this->success($this->data->academicSummary($student), 'Child grades retrieved.');
    }

    /**
     * The enrollment history of a linked child.
     */
    public function childEnrollments(Request $request, int $id): JsonResponse
    {
        $student = $this->linkedChild($request, $id);

        return $this->success(['items' => $this->data->enrollmentHistory($student)->values()], 'Child enrollments retrieved.');
    }

    /**
     * The documents of a linked child.
     */
    public function childDocuments(Request $request, int $id): JsonResponse
    {
        $student = $this->linkedChild($request, $id);

        return $this->success(['items' => $this->data->documents($student)->values()], 'Child documents retrieved.');
    }

    /**
     * Resolve a student only when it is linked to the authenticated parent.
     */
    protected function linkedChild(Request $request, int $id): Student
    {
        $parent = $this->identities->parent($request->user());

        $student = $parent?->students()->find($id);

        if ($student === null) {
            abort(404, 'Child not found or not linked to this account.');
        }

        return $student;
    }
}