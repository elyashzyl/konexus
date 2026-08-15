<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Exceptions\ApiException;
use App\Models\AcademicClass;
use App\Models\AcademicClassStudent;
use App\Models\AcademicPeriod;
use App\Models\AssessmentItem;
use App\Models\AssessmentScore;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\CurriculumEntry;
use App\Models\CurriculumProgram;
use App\Models\Enrollment;
use App\Models\GradeRecord;
use App\Models\PromotionDecision;
use App\Models\StudentSubjectEnrollment;
use App\Models\SubjectOffering;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AcademicOperationsService
{
    /**
     * Create a versioned program. Local changes must carry their adaptation reason.
     *
     * @param  array<string, mixed>  $data
     */
    public function createProgram(array $data): CurriculumProgram
    {
        if (($data['compliance_status'] ?? 'deped-aligned') === 'local-adaptation' && blank($data['local_adaptation_reason'] ?? null)) {
            throw ApiException::unprocessable('A local adaptation reason is required when the program is not DepEd-aligned.');
        }

        return CurriculumProgram::query()->create($data + ['is_active' => true]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addPeriod(CurriculumProgram $program, array $data): AcademicPeriod
    {
        if ($data['end_date'] < $data['start_date']) {
            throw ApiException::unprocessable('The period end date must not precede its start date.');
        }

        return $program->periods()->create($data + ['is_active' => true]);
    }

    /**
     * Materialize immutable roster and subject snapshots once enrollment is official.
     */
    public function materializeEnrollment(Enrollment $enrollment): array
    {
        if ($enrollment->curriculum_program_id === null) {
            return ['class_member_created' => false, 'subject_enrollments_created' => 0];
        }

        $program = CurriculumProgram::query()->findOrFail($enrollment->curriculum_program_id);

        if ($program->academic_year_id !== $enrollment->academic_year_id || ! $program->includesGradeLevel($enrollment->grade_level_id)) {
            throw ApiException::unprocessable('The selected curriculum program does not apply to this enrollment.');
        }

        return DB::transaction(function () use ($enrollment, $program): array {
            $class = AcademicClass::query()
                ->where('academic_year_id', $enrollment->academic_year_id)
                ->where('campus_id', $enrollment->campus_id)
                ->where('grade_level_id', $enrollment->grade_level_id)
                ->where('section_id', $enrollment->section_id)
                ->first();

            $memberCreated = false;
            if ($class !== null) {
                $member = AcademicClassStudent::query()->firstOrCreate(
                    ['academic_class_id' => $class->id, 'student_id' => $enrollment->student_id],
                    ['enrollment_id' => $enrollment->id, 'source' => AcademicClassStudent::SOURCE_ENROLLMENT, 'academic_status' => $enrollment->status, 'is_active' => true],
                );
                $memberCreated = $member->wasRecentlyCreated;
            }

            $offerings = SubjectOffering::query()
                ->with(['subject', 'curriculumEntry'])
                ->where('academic_year_id', $enrollment->academic_year_id)
                ->where('campus_id', $enrollment->campus_id)
                ->where('grade_level_id', $enrollment->grade_level_id)
                ->where('section_id', $enrollment->section_id)
                ->where('curriculum_program_id', $program->id)
                ->where('is_active', true)
                ->get();

            $created = 0;
            foreach ($offerings as $offering) {
                $entry = $offering->curriculumEntry;
                if (! $this->isEligibleForOffering($enrollment, $entry)) {
                    continue;
                }

                $subjectEnrollment = StudentSubjectEnrollment::query()->firstOrCreate(
                    ['student_id' => $enrollment->student_id, 'subject_offering_id' => $offering->id],
                    [
                        'enrollment_id' => $enrollment->id,
                        'curriculum_program_id' => $program->id,
                        'curriculum_entry_id' => $entry?->id,
                        'status' => 'enrolled',
                        'subject_snapshot' => ['id' => $offering->subject_id, 'code' => $offering->subject?->code, 'name' => $offering->subject?->name, 'units' => $offering->units],
                        'assessment_policy_snapshot' => $entry?->assessment_policy,
                    ],
                );

                $created += $subjectEnrollment->wasRecentlyCreated ? 1 : 0;
            }

            return ['class_member_created' => $memberCreated, 'subject_enrollments_created' => $created];
        });
    }

    /**
     * @param  array{academic_class_id: int, attendance_date: string}  $data
     */
    public function createAttendanceSession(array $data): AttendanceSession
    {
        return AttendanceSession::query()->firstOrCreate(
            ['academic_class_id' => $data['academic_class_id'], 'attendance_date' => $data['attendance_date']],
            ['status' => 'open', 'recorded_by' => auth()->id()],
        );
    }

    /**
     * @param  list<array{student_id: int, status: string, minutes_late?: int, remarks?: string|null}>  $records
     */
    public function recordAttendance(AttendanceSession $session, array $records): AttendanceSession
    {
        if ($session->status === 'submitted') {
            throw ApiException::unprocessable('Submitted attendance cannot be changed.');
        }

        $roster = $session->academicClass->activeMembers()->pluck('student_id');
        foreach ($records as $record) {
            if (! $roster->contains($record['student_id'])) {
                throw ApiException::unprocessable('Attendance can only be recorded for active class members.');
            }

            AttendanceRecord::query()->updateOrCreate(
                ['attendance_session_id' => $session->id, 'student_id' => $record['student_id']],
                ['status' => $record['status'], 'minutes_late' => $record['minutes_late'] ?? 0, 'remarks' => $record['remarks'] ?? null, 'recorded_by' => auth()->id()],
            );
        }

        return $session->fresh(['records.student']);
    }

    public function submitAttendance(AttendanceSession $session): AttendanceSession
    {
        if ($session->status === 'submitted') {
            return $session;
        }

        $rosterCount = $session->academicClass->activeMembers()->count();
        if ($session->records()->count() !== $rosterCount) {
            throw ApiException::unprocessable('Every active class member must have an attendance status before submission.');
        }

        $session->update(['status' => 'submitted', 'submitted_at' => now()]);

        return $session->fresh(['records.student']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAssessment(array $data): AssessmentItem
    {
        $offering = SubjectOffering::query()->findOrFail($data['subject_offering_id']);
        $period = AcademicPeriod::query()->findOrFail($data['academic_period_id']);
        if ($offering->curriculum_program_id !== $period->curriculum_program_id) {
            throw ApiException::unprocessable('The assessment period must belong to the offering curriculum program.');
        }

        return AssessmentItem::query()->create($data);
    }

    /**
     * @param  list<array{student_subject_enrollment_id: int, score: float|int|null}>  $scores
     */
    public function recordScores(AssessmentItem $assessment, array $scores): AssessmentItem
    {
        foreach ($scores as $score) {
            if ($score['score'] !== null && $score['score'] > $assessment->max_score) {
                throw ApiException::unprocessable('A score cannot exceed the assessment maximum.');
            }

            $subjectEnrollment = StudentSubjectEnrollment::query()->findOrFail($score['student_subject_enrollment_id']);
            if ($subjectEnrollment->subject_offering_id !== $assessment->subject_offering_id) {
                throw ApiException::unprocessable('A score must belong to the assessment offering.');
            }

            AssessmentScore::query()->updateOrCreate(
                ['assessment_item_id' => $assessment->id, 'student_subject_enrollment_id' => $subjectEnrollment->id],
                ['score' => $score['score'], 'recorded_by' => auth()->id()],
            );
            $this->recalculateGrade($subjectEnrollment, $assessment->academic_period_id);
        }

        return $assessment->fresh('scores');
    }

    public function decidePromotion(Enrollment $enrollment, ?string $overrideDecision = null, ?string $overrideReason = null): PromotionDecision
    {
        if ($enrollment->status !== EnrollmentStatus::OFFICIALLY_ENROLLED->value) {
            throw ApiException::unprocessable('Only an officially enrolled learner can receive a promotion decision.');
        }

        $records = GradeRecord::query()
            ->where('student_id', $enrollment->student_id)
            ->where('academic_year_id', $enrollment->academic_year_id)
            ->whereIn('status', ['approved', 'published'])
            ->whereNotNull('final_grade')
            ->get(['id', 'subject_id', 'final_grade', 'status']);

        $average = $records->avg('final_grade');
        $decision = $records->isEmpty() ? 'incomplete' : ($records->contains(fn (GradeRecord $record): bool => (float) $record->final_grade < 75) ? 'conditionally-promoted' : 'promoted');

        if ($overrideDecision !== null) {
            if (blank($overrideReason)) {
                throw ApiException::unprocessable('An override reason is required.');
            }
            $decision = $overrideDecision;
        }

        return PromotionDecision::query()->updateOrCreate(
            ['student_id' => $enrollment->student_id, 'academic_year_id' => $enrollment->academic_year_id],
            [
                'enrollment_id' => $enrollment->id,
                'grade_level_id' => $enrollment->grade_level_id,
                'decision' => $decision,
                'general_average' => $average,
                'basis_snapshot' => ['grade_record_ids' => $records->pluck('id')->all(), 'final_grades' => $records->pluck('final_grade', 'subject_id')->all()],
                'override_reason' => $overrideReason,
                'decided_by' => auth()->id(),
                'decided_at' => now(),
            ],
        );
    }

    /** @return array<string, mixed> */
    public function reportCard(Enrollment $enrollment): array
    {
        $grades = GradeRecord::query()->with(['subject', 'academicPeriod'])
            ->where('student_id', $enrollment->student_id)
            ->where('academic_year_id', $enrollment->academic_year_id)
            ->whereIn('status', ['approved', 'published'])
            ->get();
        $attendance = AttendanceRecord::query()->where('student_id', $enrollment->student_id)
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $promotion = PromotionDecision::query()->where('student_id', $enrollment->student_id)->where('academic_year_id', $enrollment->academic_year_id)->first();

        return ['label' => 'System-generated internal report card — not an official DepEd LIS form.', 'enrollment_id' => $enrollment->id, 'student_id' => $enrollment->student_id, 'grades' => $grades, 'general_average' => $grades->avg('final_grade'), 'attendance' => $attendance, 'promotion' => $promotion];
    }

    private function isEligibleForOffering(Enrollment $enrollment, ?CurriculumEntry $entry): bool
    {
        if ($entry === null || empty($entry->eligible_clusters)) {
            return true;
        }

        return $enrollment->program_cluster !== null && in_array($enrollment->program_cluster, $entry->eligible_clusters, true);
    }

    private function recalculateGrade(StudentSubjectEnrollment $subjectEnrollment, int $periodId): void
    {
        $items = AssessmentItem::query()->with('scores')->where('subject_offering_id', $subjectEnrollment->subject_offering_id)->where('academic_period_id', $periodId)->get();
        if ($items->isEmpty()) {
            return;
        }

        $weights = $subjectEnrollment->assessment_policy_snapshot ?? [];
        $componentScores = $items->groupBy('component')->map(function (Collection $componentItems) use ($subjectEnrollment): float {
            $maximum = (float) $componentItems->sum('max_score');
            $earned = (float) $componentItems->sum(fn (AssessmentItem $item): float => (float) optional($item->scores->firstWhere('student_subject_enrollment_id', $subjectEnrollment->id))->score);

            return $maximum > 0 ? ($earned / $maximum) * 100 : 0.0;
        });
        $weighted = 0.0;
        $weightTotal = 0.0;
        foreach ($componentScores as $component => $score) {
            $weight = (float) ($weights[$component] ?? 1);
            $weighted += $score * $weight;
            $weightTotal += $weight;
        }
        $final = $weightTotal > 0 ? round($weighted / $weightTotal, 2) : null;
        $offering = $subjectEnrollment->subjectOffering;

        GradeRecord::query()->updateOrCreate(
            ['student_subject_enrollment_id' => $subjectEnrollment->id, 'academic_period_id' => $periodId],
            ['student_id' => $subjectEnrollment->student_id, 'academic_year_id' => $offering->academic_year_id, 'academic_term_id' => $offering->academic_term_id, 'grade_level_id' => $offering->grade_level_id, 'section_id' => $offering->section_id, 'subject_id' => $offering->subject_id, 'subject_offering_id' => $offering->id, 'teacher_id' => $offering->teacher_id, 'raw_grade' => $final, 'final_grade' => $final, 'status' => 'draft'],
        );
    }
}
