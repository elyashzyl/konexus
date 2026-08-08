<?php

namespace App\Providers;

use App\Repositories\AcademicTermRepository;
use App\Repositories\AcademicYearRepository;
use App\Repositories\AnnouncementRepository;
use App\Repositories\BuildingRepository;
use App\Repositories\CampusRepository;
use App\Repositories\Contracts\AcademicTermRepositoryInterface;
use App\Repositories\Contracts\AcademicYearRepositoryInterface;
use App\Repositories\Contracts\AnnouncementRepositoryInterface;
use App\Repositories\Contracts\BuildingRepositoryInterface;
use App\Repositories\Contracts\CampusRepositoryInterface;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\GradeLevelRepositoryInterface;
use App\Repositories\Contracts\MasterDataRepositoryInterface;
use App\Repositories\Contracts\RoomRepositoryInterface;
use App\Repositories\Contracts\SchoolCalendarEventRepositoryInterface;
use App\Repositories\Contracts\SchoolProfileRepositoryInterface;
use App\Repositories\Contracts\SectionRepositoryInterface;
use App\Repositories\Contracts\SubjectRepositoryInterface;
use App\Repositories\Contracts\SystemSettingRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\DepartmentRepository;
use App\Repositories\GradeLevelRepository;
use App\Repositories\MasterDataRepository;
use App\Repositories\RoomRepository;
use App\Repositories\SchoolCalendarEventRepository;
use App\Repositories\SchoolProfileRepository;
use App\Repositories\SectionRepository;
use App\Repositories\SubjectRepository;
use App\Repositories\SystemSettingRepository;
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
    }

    /**
     * Bootstrap any repository services.
     */
    public function boot(): void
    {
        //
    }
}
