<?php

use App\Http\Controllers\Api\V1\AcademicTermController;
use App\Http\Controllers\Api\V1\AcademicYearController;
use App\Http\Controllers\Api\V1\AnnouncementController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\PasswordController;
use App\Http\Controllers\Api\V1\Auth\SessionController;
use App\Http\Controllers\Api\V1\BuildingController;
use App\Http\Controllers\Api\V1\CampusController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\GradeLevelController;
use App\Http\Controllers\Api\V1\MasterDataController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\RoomController;
use App\Http\Controllers\Api\V1\SchoolCalendarEventController;
use App\Http\Controllers\Api\V1\SchoolProfileController;
use App\Http\Controllers\Api\V1\SectionController;
use App\Http\Controllers\Api\V1\SubjectController;
use App\Http\Controllers\Api\V1\SystemSettingController;
use Illuminate\Support\Facades\Route;

/**
 * Register the standard CRUD + restore + force-delete routes for a module.
 */
$crudRoutes = static function (string $prefix, string $name, string $controller): void {
    Route::prefix($prefix)->name($name.'.')->group(static function () use ($controller): void {
        Route::get('/', [$controller, 'index'])->name('index');
        Route::post('/', [$controller, 'store'])->name('store');
        Route::get('/{id}', [$controller, 'show'])->name('show');
        Route::put('/{id}', [$controller, 'update'])->name('update');
        Route::patch('/{id}', [$controller, 'update'])->name('partial-update');
        Route::delete('/{id}', [$controller, 'destroy'])->name('destroy');
        Route::post('/{id}/restore', [$controller, 'restore'])->name('restore');
        Route::delete('/{id}/force', [$controller, 'forceDestroy'])->name('force-destroy');
    });
};

Route::prefix('v1')->name('api.v1.')->group(function () use ($crudRoutes): void {
    // ─────────────────────────────────────────────
    // Public authentication endpoints
    // ─────────────────────────────────────────────
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:login')
            ->name('login');

        Route::post('register', [AuthController::class, 'register'])
            ->middleware('throttle:register')
            ->name('register');

        Route::post('forgot-password', [PasswordController::class, 'forgotPassword'])
            ->middleware('throttle:password')
            ->name('password.email');

        Route::post('reset-password', [PasswordController::class, 'resetPassword'])
            ->middleware('throttle:password')
            ->name('password.store');

        // ─────────────────────────────────────────
        // Authenticated authentication endpoints
        // ─────────────────────────────────────────
        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('me', [AuthController::class, 'me'])->name('me');
            Route::patch('me', [AuthController::class, 'update'])->name('me.update');
            Route::delete('me', [AuthController::class, 'destroy'])->name('me.destroy');
            Route::put('password', [AuthController::class, 'changePassword'])->name('password.update');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');

            Route::get('sessions', [SessionController::class, 'index'])->name('sessions.index');
            Route::delete('sessions', [SessionController::class, 'destroyAll'])->name('sessions.destroy-all');
            Route::delete('sessions/{token}', [SessionController::class, 'destroy'])->name('sessions.destroy');
        });
    });

    // ─────────────────────────────────────────
    // Foundation endpoints
    // ─────────────────────────────────────────
    // Lightweight catalog used to populate public dropdowns (e.g. registration role picker).
    Route::get('roles/catalog', [RoleController::class, 'catalog'])->name('roles.catalog');

    // ─────────────────────────────────────────
    // Phase 2 core modules
    // ─────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () use ($crudRoutes): void {
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');

        $crudRoutes('system-settings', 'system-settings', SystemSettingController::class);
        $crudRoutes('school-profiles', 'school-profiles', SchoolProfileController::class);
        $crudRoutes('campuses', 'campuses', CampusController::class);
        $crudRoutes('academic-years', 'academic-years', AcademicYearController::class);
        $crudRoutes('academic-terms', 'academic-terms', AcademicTermController::class);
        $crudRoutes('grade-levels', 'grade-levels', GradeLevelController::class);
        $crudRoutes('departments', 'departments', DepartmentController::class);
        $crudRoutes('subjects', 'subjects', SubjectController::class);
        $crudRoutes('buildings', 'buildings', BuildingController::class);
        $crudRoutes('rooms', 'rooms', RoomController::class);
        $crudRoutes('sections', 'sections', SectionController::class);
        $crudRoutes('school-calendar', 'school-calendar', SchoolCalendarEventController::class);
        $crudRoutes('announcements', 'announcements', AnnouncementController::class);
        $crudRoutes('master-data', 'master-data', MasterDataController::class);
    });
});
