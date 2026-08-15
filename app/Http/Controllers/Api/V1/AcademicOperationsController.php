<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\AcademicOperationsRequest;
use App\Models\AssessmentItem;
use App\Models\AttendanceSession;
use App\Models\CurriculumProgram;
use App\Models\Enrollment;
use App\Services\AcademicOperationsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class AcademicOperationsController extends ApiController
{
    use AuthorizesRequests;

    public function __construct(private readonly AcademicOperationsService $operations) {}

    public function programs(): JsonResponse
    {
        return $this->success(CurriculumProgram::query()->with('periods')->latest()->get(), 'Curriculum programs retrieved.');
    }

    public function storeProgram(AcademicOperationsRequest $request): JsonResponse
    {
        return $this->success($this->operations->createProgram($request->validated()), 'Curriculum program created.', 201);
    }

    public function storePeriod(AcademicOperationsRequest $request, CurriculumProgram $program): JsonResponse
    {
        return $this->success($this->operations->addPeriod($program, $request->validated()), 'Academic period created.', 201);
    }

    public function materializeEnrollment(Enrollment $enrollment): JsonResponse
    {
        return $this->success($this->operations->materializeEnrollment($enrollment), 'Enrollment placement materialized.');
    }

    public function storeAttendanceSession(AcademicOperationsRequest $request): JsonResponse
    {
        return $this->success($this->operations->createAttendanceSession($request->validated()), 'Attendance session created.', 201);
    }

    public function recordAttendance(AcademicOperationsRequest $request, AttendanceSession $attendanceSession): JsonResponse
    {
        return $this->success($this->operations->recordAttendance($attendanceSession, $request->validated('records')), 'Attendance recorded.');
    }

    public function submitAttendance(AttendanceSession $attendanceSession): JsonResponse
    {
        return $this->success($this->operations->submitAttendance($attendanceSession), 'Attendance submitted.');
    }

    public function storeAssessment(AcademicOperationsRequest $request): JsonResponse
    {
        return $this->success($this->operations->createAssessment($request->validated()), 'Assessment created.', 201);
    }

    public function recordScores(AcademicOperationsRequest $request, AssessmentItem $assessment): JsonResponse
    {
        return $this->success($this->operations->recordScores($assessment, $request->validated('scores')), 'Assessment scores recorded.');
    }

    public function decidePromotion(AcademicOperationsRequest $request, Enrollment $enrollment): JsonResponse
    {
        return $this->success($this->operations->decidePromotion($enrollment, $request->validated('decision'), $request->validated('override_reason')), 'Promotion decision recorded.');
    }

    public function reportCard(Enrollment $enrollment): JsonResponse
    {
        return $this->success($this->operations->reportCard($enrollment), 'Internal report card retrieved.');
    }
}
