<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EnrollmentStatus;
use App\Exceptions\ApiException;
use App\Http\Requests\Api\PublicEnrollmentDetailsRequest;
use App\Http\Requests\Api\PublicEnrollmentFamilyRequest;
use App\Http\Requests\Api\PublicEnrollmentStudentRequest;
use App\Http\Requests\Api\WalkInEnrollmentRequest;
use App\Models\Enrollment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles the authenticated (walk-in / manual) enrollment wizard.
 *
 * Mirrors the online enrollment flow part-by-part so the on-site registrar can
 * walk a family through the exact same application. Walk-in applications are
 * created as drafts and are pushed through the approval chain only when the
 * wizard is finished and the application is submitted.
 */
class WalkInEnrollmentController extends PublicEnrollmentController
{
    use AuthorizesRequests;
    /**
     * Create a Part 1 walk-in enrollment application as a draft.
     */
    public function apply(WalkInEnrollmentRequest $request): JsonResponse
    {
        $this->authorize('create', Enrollment::class);

        $enrollment = $this->createApplication($request->validated(), EnrollmentStatus::DRAFT->value, false);

        return $this->success([
            'id' => $enrollment->id,
            'reference_number' => $enrollment->reference_number,
            'status' => $enrollment->status,
        ], 'Enrollment draft created.', 201);
    }

    /**
     * Retrieve a draft application so the wizard can be resumed.
     */
    public function show(Enrollment $enrollment): JsonResponse
    {
        $this->authorize('view', $enrollment);

        return parent::show($enrollment);
    }

    /**
     * Store or update the Part 2 student information.
     */
    public function storeStudent(Enrollment $enrollment, PublicEnrollmentStudentRequest $request): JsonResponse
    {
        $this->authorize('update', $enrollment);
        $this->ensureDraft($enrollment);

        return parent::storeStudent($enrollment, $request);
    }

    /**
     * Upload the student's 2x2 photo.
     */
    public function storeStudentPhoto(Enrollment $enrollment, Request $request): JsonResponse
    {
        $this->authorize('update', $enrollment);
        $this->ensureDraft($enrollment);

        return parent::storeStudentPhoto($enrollment, $request);
    }

    /**
     * Store or update the Part 3 family background.
     */
    public function storeFamily(Enrollment $enrollment, PublicEnrollmentFamilyRequest $request): JsonResponse
    {
        $this->authorize('update', $enrollment);
        $this->ensureDraft($enrollment);

        return parent::storeFamily($enrollment, $request);
    }

    /**
     * Store or update the Parts 4-8 details.
     */
    public function storeDetails(Enrollment $enrollment, PublicEnrollmentDetailsRequest $request): JsonResponse
    {
        $this->authorize('update', $enrollment);
        $this->ensureDraft($enrollment);

        return parent::storeDetails($enrollment, $request);
    }

    /**
     * Capture a digital signature for the student or parent/guardian.
     */
    public function storeSignature(Enrollment $enrollment, Request $request): JsonResponse
    {
        $this->authorize('update', $enrollment);
        $this->ensureDraft($enrollment);

        return parent::storeSignature($enrollment, $request);
    }

    /**
     * Ensure the walk-in wizard may only edit drafts.
     *
     * @throws ApiException
     */
    private function ensureDraft(Enrollment $enrollment): void
    {
        if ($enrollment->status !== EnrollmentStatus::DRAFT->value) {
            throw ApiException::unprocessable('Only draft enrollments can be edited through the walk-in wizard.');
        }
    }
}