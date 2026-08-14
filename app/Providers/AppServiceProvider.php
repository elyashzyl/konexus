<?php

namespace App\Providers;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Building;
use App\Models\Campus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Enrollment;
use App\Models\EnrollmentDocument;
use App\Models\EnrollmentRequirement;
use App\Models\EnrollmentSignature;
use App\Models\GradeLevel;
use App\Models\Guardian;
use App\Models\License;
use App\Models\MasterData;
use App\Models\ParentGuardian;
use App\Models\Room;
use App\Models\SchoolCalendarEvent;
use App\Models\SchoolProfile;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Subscription;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionSetting;
use App\Models\SubscriptionUsage;
use App\Models\SystemSetting;
use App\Models\Teacher;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\AcademicTermPolicy;
use App\Policies\AcademicYearPolicy;
use App\Policies\AnnouncementPolicy;
use App\Policies\AuditPolicy;
use App\Policies\BuildingPolicy;
use App\Policies\CampusPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\EnrollmentDocumentPolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\EnrollmentRequirementPolicy;
use App\Policies\EnrollmentSignaturePolicy;
use App\Policies\FeaturePolicy;
use App\Policies\GradeLevelPolicy;
use App\Policies\GuardianPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LicensePolicy;
use App\Policies\MasterDataPolicy;
use App\Policies\ParentPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\RoomPolicy;
use App\Policies\SchoolCalendarEventPolicy;
use App\Policies\SchoolProfilePolicy;
use App\Policies\SectionPolicy;
use App\Policies\StaffPolicy;
use App\Policies\StudentPolicy;
use App\Policies\SubjectPolicy;
use App\Policies\SubscriptionPlanPolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\SubscriptionSettingPolicy;
use App\Policies\SystemSettingPolicy;
use App\Policies\TeacherPolicy;
use App\Policies\TenantPolicy;
use App\Policies\UsagePolicy;
use App\Policies\UserPolicy;
use App\Listeners\EnrollmentStatusChangedListener;
use App\Events\EnrollmentStatusChanged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);

        // Phase 2 foundation module policies
        Gate::policy(SystemSetting::class, SystemSettingPolicy::class);
        Gate::policy(SchoolProfile::class, SchoolProfilePolicy::class);
        Gate::policy(Campus::class, CampusPolicy::class);
        Gate::policy(AcademicYear::class, AcademicYearPolicy::class);
        Gate::policy(AcademicTerm::class, AcademicTermPolicy::class);
        Gate::policy(GradeLevel::class, GradeLevelPolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Subject::class, SubjectPolicy::class);
        Gate::policy(Building::class, BuildingPolicy::class);
        Gate::policy(Room::class, RoomPolicy::class);
        Gate::policy(Section::class, SectionPolicy::class);
        Gate::policy(SchoolCalendarEvent::class, SchoolCalendarEventPolicy::class);
        Gate::policy(Announcement::class, AnnouncementPolicy::class);
        Gate::policy(MasterData::class, MasterDataPolicy::class);

        // Part 3 – People Management policies
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(ParentGuardian::class, ParentPolicy::class);
        Gate::policy(Guardian::class, GuardianPolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(Teacher::class, TeacherPolicy::class);
        Gate::policy(Staff::class, StaffPolicy::class);

        // Part 4 – Enrollment Management policies
        Gate::policy(Enrollment::class, EnrollmentPolicy::class);
        Gate::policy(EnrollmentRequirement::class, EnrollmentRequirementPolicy::class);
        Gate::policy(EnrollmentDocument::class, EnrollmentDocumentPolicy::class);
        Gate::policy(EnrollmentSignature::class, EnrollmentSignaturePolicy::class);

        // Part 4 – Enrollment workflow listeners
        Event::listen(EnrollmentStatusChanged::class, EnrollmentStatusChangedListener::class);

        // Part 10 – Platform subscription & license policies
        Gate::policy(Tenant::class, TenantPolicy::class);
        Gate::policy(SubscriptionPlan::class, SubscriptionPlanPolicy::class);
        Gate::policy(Subscription::class, SubscriptionPolicy::class);
        Gate::policy(SubscriptionInvoice::class, InvoicePolicy::class);
        Gate::policy(SubscriptionPayment::class, PaymentPolicy::class);
        Gate::policy(License::class, LicensePolicy::class);
        Gate::policy(SubscriptionUsage::class, UsagePolicy::class);
        Gate::policy(SubscriptionHistory::class, AuditPolicy::class);
        Gate::policy(SubscriptionSetting::class, SubscriptionSettingPolicy::class);

        // Foundation gates. These are role-based for now; granular
        // permissions will be layered on top in a later phase.
        Gate::define('manage-users', fn (User $user) => $user->hasAnyRole(['super-administrator', 'school-administrator']));
        Gate::define('manage-roles', fn (User $user) => $user->hasRole('super-administrator'));
        Gate::define('manage-settings', fn (User $user) => $user->hasAnyRole(['super-administrator', 'school-administrator']));
        Gate::define('view-module', fn (User $user, string $module) => $user->hasRole('super-administrator') || $user->can($module));
    }
}
