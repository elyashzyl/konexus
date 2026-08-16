<?php

namespace App\Providers;

use App\Enums\RoleEnum;
use App\Models\AcademicClass;
use App\Models\AcademicClassStudent;
use App\Models\AcademicPeriod;
use App\Models\AcademicSetting;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\AssessmentItem;
use App\Models\AssessmentScore;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Building;
use App\Models\Campus;
use App\Models\ClassSchedule;
use App\Models\CurriculumEntry;
use App\Models\CurriculumProgram;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Enrollment;
use App\Models\EnrollmentCapacityOverride;
use App\Models\EnrollmentDocument;
use App\Models\EnrollmentRequirement;
use App\Models\EnrollmentRequirementItem;
use App\Models\EnrollmentSignature;
use App\Models\EnrollmentTransfer;
use App\Models\GradeCorrection;
use App\Models\GradeLevel;
use App\Models\GradeRecord;
use App\Models\GradeScale;
use App\Models\GradeScaleEntry;
use App\Models\Guardian;
use App\Models\MasterData;
use App\Models\ParentGuardian;
use App\Models\PromotionDecision;
use App\Models\Room;
use App\Models\SchoolCalendarEvent;
use App\Models\SchoolProfile;
use App\Models\Scopes\CampusScope;
use App\Models\Scopes\SchoolScope;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\StudentSubjectEnrollment;
use App\Models\Subject;
use App\Models\SubjectOffering;
use App\Models\SubscriptionSetting;
use App\Models\SystemSetting;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\Tenant;
use App\Models\Tuition;
use App\Models\User;
use App\Support\CampusContext;
use App\Support\SchoolContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

/**
 * Enforces per-school data isolation.
 *
 * Every school-owned model gets a global {@see SchoolScope} that filters reads
 * by the authenticated user's school (platform administrators are exempt).
 * Records created while a school user is active are anchored to that school;
 * when no user is present (seeding) they fall back to the default school so
 * seeded demo data remains visible to school accounts.
 */
class SchoolScopingServiceProvider extends ServiceProvider
{
    /**
     * Models whose records belong to a single school.
     *
     * @var list<class-string<Model>>
     */
    private const SCOPED_MODELS = [
        SchoolProfile::class,
        Campus::class,
        User::class,
        Tenant::class,
        AcademicYear::class,
        AcademicTerm::class,
        AcademicPeriod::class,
        AcademicSetting::class,
        AcademicClass::class,
        Announcement::class,
        Building::class,
        ClassSchedule::class,
        AssessmentItem::class,
        AssessmentScore::class,
        AttendanceRecord::class,
        AttendanceSession::class,
        CurriculumEntry::class,
        CurriculumProgram::class,
        Department::class,
        Employee::class,
        Enrollment::class,
        EnrollmentCapacityOverride::class,
        EnrollmentDocument::class,
        EnrollmentRequirement::class,
        EnrollmentRequirementItem::class,
        EnrollmentSignature::class,
        EnrollmentTransfer::class,
        GradeLevel::class,
        GradeRecord::class,
        GradeCorrection::class,
        GradeScale::class,
        GradeScaleEntry::class,
        PromotionDecision::class,
        Guardian::class,
        MasterData::class,
        ParentGuardian::class,
        Room::class,
        SchoolCalendarEvent::class,
        Section::class,
        Staff::class,
        Student::class,
        StudentDocument::class,
        Subject::class,
        SubjectOffering::class,
        StudentSubjectEnrollment::class,
        SubscriptionSetting::class,
        SystemSetting::class,
        Teacher::class,
        TeacherAssignment::class,
        Tuition::class,
    ];

    /**
     * Models that inherit the current user's school when created.
     *
     * SchoolProfile, User and Tenant are excluded because their anchoring is
     * managed explicitly by the registration, user management and tenant flows.
     *
     * @var list<class-string<Model>>
     */
    private const AUTO_ANCHORED_MODELS = [
        Campus::class,
        AcademicYear::class,
        AcademicTerm::class,
        AcademicPeriod::class,
        AcademicSetting::class,
        AcademicClass::class,
        Announcement::class,
        Building::class,
        ClassSchedule::class,
        AssessmentItem::class,
        AssessmentScore::class,
        AttendanceRecord::class,
        AttendanceSession::class,
        CurriculumEntry::class,
        CurriculumProgram::class,
        Department::class,
        Employee::class,
        Enrollment::class,
        EnrollmentCapacityOverride::class,
        EnrollmentDocument::class,
        EnrollmentRequirement::class,
        EnrollmentRequirementItem::class,
        EnrollmentSignature::class,
        EnrollmentTransfer::class,
        GradeLevel::class,
        GradeRecord::class,
        GradeCorrection::class,
        GradeScale::class,
        GradeScaleEntry::class,
        PromotionDecision::class,
        Guardian::class,
        MasterData::class,
        ParentGuardian::class,
        Room::class,
        SchoolCalendarEvent::class,
        Section::class,
        Staff::class,
        Student::class,
        StudentDocument::class,
        Subject::class,
        SubjectOffering::class,
        StudentSubjectEnrollment::class,
        SubscriptionSetting::class,
        SystemSetting::class,
        Teacher::class,
        TeacherAssignment::class,
        Tuition::class,
    ];

    /**
     * Operational records isolated by the active campus workspace.
     *
     * School-wide master data remains shared by the school profile while the
     * roster, attendance, gradebook, and enrollment flows stay campus-local.
     *
     * @var list<class-string<Model>>
     */
    private const CAMPUS_SCOPED_MODELS = [
        AcademicClass::class,
        AcademicClassStudent::class,
        Announcement::class,
        AssessmentItem::class,
        AssessmentScore::class,
        AttendanceRecord::class,
        AttendanceSession::class,
        Building::class,
        ClassSchedule::class,
        CurriculumEntry::class,
        Department::class,
        Enrollment::class,
        EnrollmentCapacityOverride::class,
        EnrollmentDocument::class,
        EnrollmentRequirementItem::class,
        EnrollmentSignature::class,
        EnrollmentTransfer::class,
        GradeCorrection::class,
        GradeLevel::class,
        GradeRecord::class,
        PromotionDecision::class,
        Room::class,
        SchoolCalendarEvent::class,
        Section::class,
        StudentSubjectEnrollment::class,
        Subject::class,
        SubjectOffering::class,
        TeacherAssignment::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        foreach (self::SCOPED_MODELS as $model) {
            $model::addGlobalScope(new SchoolScope);
        }

        foreach (self::CAMPUS_SCOPED_MODELS as $model) {
            $model::addGlobalScope(new CampusScope);
        }

        foreach (self::AUTO_ANCHORED_MODELS as $model) {
            $model::creating(function (Model $record): void {
                $this->applySchoolAnchor($record);
                $this->applyCampusAnchor($record);
            });
        }
    }

    /**
     * Assign the school anchor to a record about to be created.
     */
    private function applySchoolAnchor(Model $record): void
    {
        if ($record->getAttribute('school_profile_id') !== null) {
            return;
        }

        $user = SchoolContext::user();

        if ($user !== null) {
            if ($user->hasRole(RoleEnum::SUPER_ADMINISTRATOR->roleName())
                || $user->hasRole(RoleEnum::PLATFORM_ADMINISTRATOR->roleName())) {
                return;
            }

            $record->setAttribute('school_profile_id', $user->school_profile_id);

            return;
        }

        $record->setAttribute('school_profile_id', SchoolProfile::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id'));
    }

    /**
     * Anchor new campus-owned records to the selected workspace.
     */
    private function applyCampusAnchor(Model $record): void
    {
        if (! in_array($record::class, self::CAMPUS_SCOPED_MODELS, true)
            || ! in_array('campus_id', $record->getFillable(), true)
            || CampusContext::id() === null) {
            return;
        }

        $record->setAttribute('campus_id', CampusContext::id());
    }
}
