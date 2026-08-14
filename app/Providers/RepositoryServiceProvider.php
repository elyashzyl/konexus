<?php

namespace App\Providers;

use App\Repositories\AcademicClassRepository;
use App\Repositories\AcademicClassStudentRepository;
use App\Repositories\AcademicSettingRepository;
use App\Repositories\AcademicTermRepository;
use App\Repositories\AcademicYearRepository;
use App\Repositories\AnnouncementRepository;
use App\Repositories\BuildingRepository;
use App\Repositories\CampusRepository;
use App\Repositories\ClassScheduleRepository;
use App\Repositories\Contracts\AcademicClassRepositoryInterface;
use App\Repositories\Contracts\AcademicClassStudentRepositoryInterface;
use App\Repositories\Contracts\AcademicSettingRepositoryInterface;
use App\Repositories\Contracts\AcademicTermRepositoryInterface;
use App\Repositories\Contracts\AcademicYearRepositoryInterface;
use App\Repositories\Contracts\AnnouncementRepositoryInterface;
use App\Repositories\Contracts\BuildingRepositoryInterface;
use App\Repositories\Contracts\CampusRepositoryInterface;
use App\Repositories\Contracts\ClassScheduleRepositoryInterface;
use App\Repositories\Contracts\CurriculumEntryRepositoryInterface;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\GradeCorrectionRepositoryInterface;
use App\Repositories\Contracts\GradeLevelRepositoryInterface;
use App\Repositories\Contracts\GradeRecordRepositoryInterface;
use App\Repositories\Contracts\GradeScaleEntryRepositoryInterface;
use App\Repositories\Contracts\LicenseRepositoryInterface;
use App\Repositories\Contracts\SubscriptionFeatureRepositoryInterface;
use App\Repositories\Contracts\SubscriptionHistoryRepositoryInterface;
use App\Repositories\Contracts\SubscriptionInvoiceRepositoryInterface;
use App\Repositories\Contracts\SubscriptionPaymentRepositoryInterface;
use App\Repositories\Contracts\SubscriptionPlanFeatureRepositoryInterface;
use App\Repositories\Contracts\SubscriptionPlanRepositoryInterface;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use App\Repositories\Contracts\SubscriptionSettingRepositoryInterface;
use App\Repositories\Contracts\SubscriptionUsageRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\GradeScaleRepositoryInterface;
use App\Repositories\Contracts\GuardianRepositoryInterface;
use App\Repositories\Contracts\EnrollmentCapacityOverrideRepositoryInterface;
use App\Repositories\Contracts\EnrollmentDocumentRepositoryInterface;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use App\Repositories\Contracts\EnrollmentRequirementItemRepositoryInterface;
use App\Repositories\Contracts\EnrollmentRequirementRepositoryInterface;
use App\Repositories\Contracts\EnrollmentSignatureRepositoryInterface;
use App\Repositories\Contracts\EnrollmentTransferRepositoryInterface;
use App\Repositories\Contracts\MasterDataRepositoryInterface;
use App\Repositories\Contracts\ParentRepositoryInterface;
use App\Repositories\Contracts\RoomRepositoryInterface;
use App\Repositories\Contracts\SchoolCalendarEventRepositoryInterface;
use App\Repositories\Contracts\SchoolProfileRepositoryInterface;
use App\Repositories\Contracts\SectionRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\StudentDocumentRepositoryInterface;
use App\Repositories\Contracts\StudentRepositoryInterface;
use App\Repositories\Contracts\SubjectOfferingRepositoryInterface;
use App\Repositories\Contracts\SubjectRepositoryInterface;
use App\Repositories\Contracts\SystemSettingRepositoryInterface;
use App\Repositories\Contracts\TeacherAssignmentRepositoryInterface;
use App\Repositories\Contracts\TeacherRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\CurriculumEntryRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\EnrollmentCapacityOverrideRepository;
use App\Repositories\EnrollmentDocumentRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\EnrollmentRequirementItemRepository;
use App\Repositories\EnrollmentRequirementRepository;
use App\Repositories\EnrollmentSignatureRepository;
use App\Repositories\EnrollmentTransferRepository;
use App\Repositories\GradeCorrectionRepository;
use App\Repositories\GradeLevelRepository;
use App\Repositories\GradeRecordRepository;
use App\Repositories\GradeScaleEntryRepository;
use App\Repositories\GradeScaleRepository;
use App\Repositories\GuardianRepository;
use App\Repositories\LicenseRepository;
use App\Repositories\MasterDataRepository;
use App\Repositories\ParentRepository;
use App\Repositories\RoomRepository;
use App\Repositories\SchoolCalendarEventRepository;
use App\Repositories\SchoolProfileRepository;
use App\Repositories\SectionRepository;
use App\Repositories\StaffRepository;
use App\Repositories\StudentDocumentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\SubjectOfferingRepository;
use App\Repositories\SubjectRepository;
use App\Repositories\SubscriptionFeatureRepository;
use App\Repositories\SubscriptionHistoryRepository;
use App\Repositories\SubscriptionInvoiceRepository;
use App\Repositories\SubscriptionPaymentRepository;
use App\Repositories\SubscriptionPlanFeatureRepository;
use App\Repositories\SubscriptionPlanRepository;
use App\Repositories\SubscriptionRepository;
use App\Repositories\SubscriptionSettingRepository;
use App\Repositories\SubscriptionUsageRepository;
use App\Repositories\SystemSettingRepository;
use App\Repositories\TeacherAssignmentRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\TenantRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register interface-to-implementation bindings.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        // Phase 2 foundation modules
        $this->app->bind(SystemSettingRepositoryInterface::class, SystemSettingRepository::class);
        $this->app->bind(SchoolProfileRepositoryInterface::class, SchoolProfileRepository::class);
        $this->app->bind(CampusRepositoryInterface::class, CampusRepository::class);
        $this->app->bind(AcademicYearRepositoryInterface::class, AcademicYearRepository::class);
        $this->app->bind(AcademicTermRepositoryInterface::class, AcademicTermRepository::class);
        $this->app->bind(GradeLevelRepositoryInterface::class, GradeLevelRepository::class);
        $this->app->bind(DepartmentRepositoryInterface::class, DepartmentRepository::class);
        $this->app->bind(SubjectRepositoryInterface::class, SubjectRepository::class);
        $this->app->bind(BuildingRepositoryInterface::class, BuildingRepository::class);
        $this->app->bind(RoomRepositoryInterface::class, RoomRepository::class);
        $this->app->bind(SectionRepositoryInterface::class, SectionRepository::class);
        $this->app->bind(SchoolCalendarEventRepositoryInterface::class, SchoolCalendarEventRepository::class);
        $this->app->bind(AnnouncementRepositoryInterface::class, AnnouncementRepository::class);
        $this->app->bind(MasterDataRepositoryInterface::class, MasterDataRepository::class);

        // Part 3 – People Management repositories
        $this->app->bind(StudentRepositoryInterface::class, StudentRepository::class);
        $this->app->bind(StudentDocumentRepositoryInterface::class, StudentDocumentRepository::class);
        $this->app->bind(ParentRepositoryInterface::class, ParentRepository::class);
        $this->app->bind(GuardianRepositoryInterface::class, GuardianRepository::class);
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeRepository::class);
        $this->app->bind(TeacherRepositoryInterface::class, TeacherRepository::class);
        $this->app->bind(StaffRepositoryInterface::class, StaffRepository::class);

        // Part 4 – Enrollment Management repositories
        $this->app->bind(EnrollmentRepositoryInterface::class, EnrollmentRepository::class);
        $this->app->bind(EnrollmentRequirementRepositoryInterface::class, EnrollmentRequirementRepository::class);
        $this->app->bind(EnrollmentRequirementItemRepositoryInterface::class, EnrollmentRequirementItemRepository::class);
        $this->app->bind(EnrollmentDocumentRepositoryInterface::class, EnrollmentDocumentRepository::class);
        $this->app->bind(EnrollmentTransferRepositoryInterface::class, EnrollmentTransferRepository::class);
        $this->app->bind(EnrollmentCapacityOverrideRepositoryInterface::class, EnrollmentCapacityOverrideRepository::class);
        $this->app->bind(EnrollmentSignatureRepositoryInterface::class, EnrollmentSignatureRepository::class);

        // Part 6 – Academic Management repositories
        $this->app->bind(CurriculumEntryRepositoryInterface::class, CurriculumEntryRepository::class);
        $this->app->bind(SubjectOfferingRepositoryInterface::class, SubjectOfferingRepository::class);
        $this->app->bind(AcademicClassRepositoryInterface::class, AcademicClassRepository::class);
        $this->app->bind(AcademicClassStudentRepositoryInterface::class, AcademicClassStudentRepository::class);
        $this->app->bind(TeacherAssignmentRepositoryInterface::class, TeacherAssignmentRepository::class);
        $this->app->bind(ClassScheduleRepositoryInterface::class, ClassScheduleRepository::class);
        $this->app->bind(GradeScaleRepositoryInterface::class, GradeScaleRepository::class);
        $this->app->bind(GradeScaleEntryRepositoryInterface::class, GradeScaleEntryRepository::class);
        $this->app->bind(GradeRecordRepositoryInterface::class, GradeRecordRepository::class);
        $this->app->bind(GradeCorrectionRepositoryInterface::class, GradeCorrectionRepository::class);
        $this->app->bind(AcademicSettingRepositoryInterface::class, AcademicSettingRepository::class);

        // Part 10 – Platform Subscription & License Management repositories
        $this->app->bind(TenantRepositoryInterface::class, TenantRepository::class);
        $this->app->bind(SubscriptionPlanRepositoryInterface::class, SubscriptionPlanRepository::class);
        $this->app->bind(SubscriptionPlanFeatureRepositoryInterface::class, SubscriptionPlanFeatureRepository::class);
        $this->app->bind(SubscriptionRepositoryInterface::class, SubscriptionRepository::class);
        $this->app->bind(SubscriptionFeatureRepositoryInterface::class, SubscriptionFeatureRepository::class);
        $this->app->bind(SubscriptionInvoiceRepositoryInterface::class, SubscriptionInvoiceRepository::class);
        $this->app->bind(SubscriptionPaymentRepositoryInterface::class, SubscriptionPaymentRepository::class);
        $this->app->bind(LicenseRepositoryInterface::class, LicenseRepository::class);
        $this->app->bind(SubscriptionUsageRepositoryInterface::class, SubscriptionUsageRepository::class);
        $this->app->bind(SubscriptionHistoryRepositoryInterface::class, SubscriptionHistoryRepository::class);
        $this->app->bind(SubscriptionSettingRepositoryInterface::class, SubscriptionSettingRepository::class);
    }

    /**
     * Bootstrap any repository services.
     */
    public function boot(): void
    {
        //
    }
}
