<?php

namespace App\Providers;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Building;
use App\Models\Campus;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\MasterData;
use App\Models\Room;
use App\Models\SchoolCalendarEvent;
use App\Models\SchoolProfile;
use App\Models\Section;
use App\Models\Subject;
use App\Models\SystemSetting;
use App\Models\User;
use App\Policies\AcademicTermPolicy;
use App\Policies\AcademicYearPolicy;
use App\Policies\AnnouncementPolicy;
use App\Policies\BuildingPolicy;
use App\Policies\CampusPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\GradeLevelPolicy;
use App\Policies\MasterDataPolicy;
use App\Policies\RoomPolicy;
use App\Policies\SchoolCalendarEventPolicy;
use App\Policies\SchoolProfilePolicy;
use App\Policies\SectionPolicy;
use App\Policies\SubjectPolicy;
use App\Policies\SystemSettingPolicy;
use App\Policies\UserPolicy;
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

        // Foundation gates. These are role-based for now; granular
        // permissions will be layered on top in a later phase.
        Gate::define('manage-users', fn (User $user) => $user->hasAnyRole(['super-administrator', 'school-administrator']));
        Gate::define('manage-roles', fn (User $user) => $user->hasRole('super-administrator'));
        Gate::define('manage-settings', fn (User $user) => $user->hasAnyRole(['super-administrator', 'school-administrator']));
        Gate::define('view-module', fn (User $user, string $module) => $user->hasRole('super-administrator') || $user->can($module));
    }
}
