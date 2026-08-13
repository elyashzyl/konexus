<?php

namespace App\Http\Controllers\Api\V1\Portal;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\PortalDataService;
use App\Services\PortalIdentityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The Student Portal.
 *
 * Part 8 – Portals. A student account only sees its own records. If no Student
 * record is linked to the account, an explicit "no profile" response is
 * returned instead of failing so the frontend can guide the student.
 */
class StudentPortalController extends ApiController
{
    public function __construct(
        private readonly PortalIdentityService $identities,
        private readonly PortalDataService $data,
    ) {}

    /**
     * The portal landing page: own profile summary, schedule and announcements.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $student = $this->identities->student($request->user());

        if ($student === null) {
            return $this->success([
                'profile' => null,
                'announcements' => [],
                'modules' => $this->data->moduleAvailability(),
            ], 'No student profile is linked to this account.');
        }

        $signature = $this->identities->audienceSignature($request->user());

        return $this->success([
            'profile' => $this->data->childSummary($student),
            'announcements' => $this->data->targetedAnnouncements($signature)->values(),
            'modules' => $this->data->moduleAvailability(),
        ], 'Student dashboard retrieved.');
    }

    /**
     * The full profile of the logged-in student.
     */
    public function profile(Request $request): JsonResponse
    {
        $student = $this->identities->student($request->user());

        if ($student === null) {
            return $this->success(['profile' => null], 'No student profile is linked to this account.');
        }

        return $this->success(['profile' => $this->data->childSummary($student)], 'Profile retrieved.');
    }

    /**
     * The class schedule of the logged-in student.
     */
    public function schedule(Request $request): JsonResponse
    {
        $student = $this->identities->student($request->user());

        if ($student === null) {
            return $this->success(['items' => []], 'No student profile is linked to this account.');
        }

        return $this->success(['items' => $this->data->schedule($student)->values()], 'Schedule retrieved.');
    }

    /**
     * The published grades / report card of the logged-in student.
     */
    public function grades(Request $request): JsonResponse
    {
        $student = $this->identities->student($request->user());

        if ($student === null) {
            return $this->success(['records' => []], 'No student profile is linked to this account.');
        }

        return $this->success($this->data->academicSummary($student), 'Grades retrieved.');
    }

    /**
     * The enrollment history of the logged-in student.
     */
    public function enrollments(Request $request): JsonResponse
    {
        $student = $this->identities->student($request->user());

        if ($student === null) {
            return $this->success(['items' => []], 'No student profile is linked to this account.');
        }

        return $this->success(['items' => $this->data->enrollmentHistory($student)->values()], 'Enrollments retrieved.');
    }

    /**
     * The documents of the logged-in student.
     */
    public function documents(Request $request): JsonResponse
    {
        $student = $this->identities->student($request->user());

        if ($student === null) {
            return $this->success(['items' => []], 'No student profile is linked to this account.');
        }

        return $this->success(['items' => $this->data->documents($student)->values()], 'Documents retrieved.');
    }
}