<?php

namespace App\Models\Scopes;

use App\Models\AcademicClassStudent;
use App\Models\AssessmentItem;
use App\Models\AssessmentScore;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\EnrollmentCapacityOverride;
use App\Models\EnrollmentDocument;
use App\Models\EnrollmentRequirementItem;
use App\Models\EnrollmentSignature;
use App\Models\EnrollmentTransfer;
use App\Models\GradeCorrection;
use App\Models\GradeRecord;
use App\Models\PromotionDecision;
use App\Models\StudentSubjectEnrollment;
use App\Support\CampusContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CampusScope implements Scope
{
    /** @var array<class-string<Model>, string> */
    private const RELATION_PATHS = [
        AcademicClassStudent::class => 'academicClass',
        StudentSubjectEnrollment::class => 'enrollment',
        AttendanceSession::class => 'academicClass',
        AttendanceRecord::class => 'session.academicClass',
        AssessmentItem::class => 'subjectOffering',
        AssessmentScore::class => 'assessmentItem.subjectOffering',
        GradeRecord::class => 'subjectOffering',
        GradeCorrection::class => 'gradeRecord.subjectOffering',
        PromotionDecision::class => 'enrollment',
        EnrollmentDocument::class => 'enrollment',
        EnrollmentRequirementItem::class => 'enrollment',
        EnrollmentSignature::class => 'enrollment',
        EnrollmentTransfer::class => 'enrollment',
        EnrollmentCapacityOverride::class => 'enrollment',
    ];

    public function apply(Builder $builder, Model $model): void
    {
        $campusId = CampusContext::id();
        if ($campusId === null) {
            return;
        }

        if (in_array('campus_id', $model->getFillable(), true)) {
            $builder->where(function (Builder $query) use ($model, $campusId): void {
                $query->where($model->qualifyColumn('campus_id'), $campusId)
                    ->orWhereNull($model->qualifyColumn('campus_id'));
            });

            return;
        }

        $relation = self::RELATION_PATHS[$model::class] ?? null;
        if ($relation !== null) {
            $builder->whereHas($relation, function (Builder $query) use ($campusId): void {
                $query->where('campus_id', $campusId)->orWhereNull('campus_id');
            });
        }
    }
}
