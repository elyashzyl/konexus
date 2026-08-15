<?php

use App\Http\Controllers\Api\V1\AcademicClassController;
use App\Http\Controllers\Api\V1\AcademicDashboardController;
use App\Http\Controllers\Api\V1\AcademicOperationsController;
use App\Http\Controllers\Api\V1\AcademicSettingController;
use App\Http\Controllers\Api\V1\AcademicTermController;
use App\Http\Controllers\Api\V1\AcademicYearController;
use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\AdminDashboardController;
use App\Http\Controllers\Api\V1\AnnouncementController;
use App\Http\Controllers\Api\V1\AuditController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\PasswordController;
use App\Http\Controllers\Api\V1\Auth\SessionController;
use App\Http\Controllers\Api\V1\Auth\SocialAuthController;
use App\Http\Controllers\Api\V1\BackupController;
use App\Http\Controllers\Api\V1\BuildingController;
use App\Http\Controllers\Api\V1\CampusController;
use App\Http\Controllers\Api\V1\CampusWorkspaceController;
use App\Http\Controllers\Api\V1\ClassScheduleController;
use App\Http\Controllers\Api\V1\CurriculumEntryController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\EnrollmentController;
use App\Http\Controllers\Api\V1\EnrollmentDocumentController;
use App\Http\Controllers\Api\V1\EnrollmentRequirementController;
use App\Http\Controllers\Api\V1\FeatureController;
use App\Http\Controllers\Api\V1\GlobalSearchController;
use App\Http\Controllers\Api\V1\GradeCorrectionController;
use App\Http\Controllers\Api\V1\GradeLevelController;
use App\Http\Controllers\Api\V1\GradeRecordController;
use App\Http\Controllers\Api\V1\GradeScaleController;
use App\Http\Controllers\Api\V1\GuardianController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\LicenseController;
use App\Http\Controllers\Api\V1\MasterDataController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ParentController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PlatformDashboardController;
use App\Http\Controllers\Api\V1\Portal\ParentPortalController;
use App\Http\Controllers\Api\V1\Portal\StudentPortalController;
use App\Http\Controllers\Api\V1\Portal\TeacherPortalController;
use App\Http\Controllers\Api\V1\PublicEnrollmentController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\RoomController;
use App\Http\Controllers\Api\V1\SchoolCalendarEventController;
use App\Http\Controllers\Api\V1\SchoolProfileController;
use App\Http\Controllers\Api\V1\SchoolSubscriptionController;
use App\Http\Controllers\Api\V1\SectionController;
use App\Http\Controllers\Api\V1\StaffController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\SubjectController;
use App\Http\Controllers\Api\V1\SubjectOfferingController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\SubscriptionPlanController;
use App\Http\Controllers\Api\V1\SubscriptionSettingController;
use App\Http\Controllers\Api\V1\SystemHealthController;
use App\Http\Controllers\Api\V1\SystemSettingController;
use App\Http\Controllers\Api\V1\SystemSettingsGroupController;
use App\Http\Controllers\Api\V1\TeacherAssignmentController;
use App\Http\Controllers\Api\V1\TeacherController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\TuitionController;
use App\Http\Controllers\Api\V1\UsageController;
use App\Http\Controllers\Api\V1\UserManagementController;
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

/**
 * People Management routes: standard CRUD + CSV export/import + per-resource options.
 */
$peopleRoutes = static function (string $prefix, string $name, string $controller): void {
    Route::prefix($prefix)->name($name.'.')->group(static function () use ($controller): void {
        Route::get('export', [$controller, 'export'])->name('export');
        Route::post('import', [$controller, 'import'])->name('import');
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

Route::prefix('v1')->name('api.v1.')->group(function () use ($crudRoutes, $peopleRoutes): void {
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

        // Social sign-in (Google / Facebook) – unauthenticated OAuth hand-off.
        Route::get('{provider}/redirect', [SocialAuthController::class, 'redirect'])
            ->middleware('throttle:social.redirect')
            ->name('social.redirect');
        Route::get('{provider}/callback', [SocialAuthController::class, 'callback'])
            ->middleware('throttle:social.callback')
            ->name('social.callback');

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

    // Public marketing catalog of active subscription plans (landing page).
    Route::get('public/plans', [SubscriptionPlanController::class, 'publicCatalog'])->name('public-plans');

    // Public online enrollment application (Part 1) – no authentication required.
    Route::get('public/enrollment/options', [PublicEnrollmentController::class, 'options'])->name('public.enrollment.options');
    Route::post('public/enrollments', [PublicEnrollmentController::class, 'store'])
        ->middleware('throttle:enrollment')
        ->name('public.enrollments.store');

    // Resume an in-progress application.
    Route::get('public/enrollments/{enrollment}', [PublicEnrollmentController::class, 'show'])
        ->name('public.enrollments.show');

    // Part 2 – Student information.
    Route::put('public/enrollments/{enrollment}/student', [PublicEnrollmentController::class, 'storeStudent'])
        ->middleware('throttle:enrollment.student')
        ->name('public.enrollments.student');
    Route::post('public/enrollments/{enrollment}/student/photo', [PublicEnrollmentController::class, 'storeStudentPhoto'])
        ->middleware('throttle:enrollment.photo')
        ->name('public.enrollments.student.photo');

    // Part 3 – Family background.
    Route::put('public/enrollments/{enrollment}/family', [PublicEnrollmentController::class, 'storeFamily'])
        ->middleware('throttle:enrollment.family')
        ->name('public.enrollments.family');

    // Parts 4-8 – Siblings, tuition plan, medical history, Chinese details, and agreements.
    Route::put('public/enrollments/{enrollment}/details', [PublicEnrollmentController::class, 'storeDetails'])
        ->middleware('throttle:enrollment.details')
        ->name('public.enrollments.details');

    // Part 9 – Digital signatures.
    Route::post('public/enrollments/{enrollment}/signature', [PublicEnrollmentController::class, 'storeSignature'])
        ->middleware('throttle:enrollment.signature')
        ->name('public.enrollments.signature');

    // ─────────────────────────────────────────
    // Phase 2 core modules
    // ─────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'campus.workspace'])->group(function () use ($crudRoutes, $peopleRoutes): void {
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');

        Route::get('workspaces', [CampusWorkspaceController::class, 'index'])->name('workspaces.index');
        Route::put('workspaces/active', [CampusWorkspaceController::class, 'select'])->name('workspaces.active');

        // Grouped system settings. Registered before the settings CRUD so the
        // `grouped` segment is never swallowed by the `{id}` route.
        Route::get('system-settings/grouped', [SystemSettingsGroupController::class, 'index'])
            ->middleware('roles:super-administrator,school-administrator')
            ->name('system-settings.grouped');
        Route::put('system-settings/grouped', [SystemSettingsGroupController::class, 'update'])
            ->middleware('roles:super-administrator,school-administrator')
            ->name('system-settings.grouped.update');

        $crudRoutes('system-settings', 'system-settings', SystemSettingController::class);

        // School Profile management is reserved for super administrators;
        // school administrators only read their own school (policy + scope).
        Route::middleware('roles:super-administrator,school-administrator')->group(function () use ($crudRoutes): void {
            $crudRoutes('school-profiles', 'school-profiles', SchoolProfileController::class);
        });

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

        // The targeted announcement feed of the current user. Registered before
        // the announcements CRUD so `mine` is never swallowed by `{id}`.
        Route::get('announcements/mine', [AnnouncementController::class, 'mine'])->name('announcements.mine');
        $crudRoutes('announcements', 'announcements', AnnouncementController::class);
        $crudRoutes('master-data', 'master-data', MasterDataController::class);

        // ─────────────────────────────────────────
        // Part 3 – People Management
        // ─────────────────────────────────────────
        $peopleRoutes('students', 'students', StudentController::class);
        $peopleRoutes('parents', 'parents', ParentController::class);
        $peopleRoutes('guardians', 'guardians', GuardianController::class);
        $peopleRoutes('employees', 'employees', EmployeeController::class);
        $peopleRoutes('teachers', 'teachers', TeacherController::class);
        $peopleRoutes('staff', 'staff', StaffController::class);

        Route::prefix('students')->name('students.')->group(function (): void {
            Route::get('/{id}/activities', [StudentController::class, 'activities'])->name('activities');
            Route::post('/{id}/photo', [StudentController::class, 'storePhoto'])->name('photo');
            Route::post('/{id}/parents/{parentId}', [StudentController::class, 'linkParent'])->name('link-parent');
            Route::delete('/{id}/parents/{parentId}', [StudentController::class, 'unlinkParent'])->name('unlink-parent');
            Route::post('/{id}/guardians/{guardianId}', [StudentController::class, 'linkGuardian'])->name('link-guardian');
            Route::delete('/{id}/guardians/{guardianId}', [StudentController::class, 'unlinkGuardian'])->name('unlink-guardian');
        });

        // ─────────────────────────────────────────
        // Part 4 – Enrollment Management
        // ─────────────────────────────────────────
        Route::prefix('enrollments')->name('enrollments.')->group(function (): void {
            Route::get('export', [EnrollmentController::class, 'export'])->name('export');
            Route::post('import', [EnrollmentController::class, 'import'])->name('import');
            Route::post('search-student', [EnrollmentController::class, 'searchStudent'])->name('search-student');
            Route::get('statistics', [EnrollmentController::class, 'statistics'])->name('statistics');
            Route::get('config', [EnrollmentController::class, 'config'])->name('config');

            Route::get('/', [EnrollmentController::class, 'index'])->name('index');
            Route::post('/', [EnrollmentController::class, 'store'])->name('store');
            Route::get('/{id}', [EnrollmentController::class, 'show'])->name('show');
            Route::put('/{id}', [EnrollmentController::class, 'update'])->name('update');
            Route::patch('/{id}', [EnrollmentController::class, 'update'])->name('partial-update');
            Route::delete('/{id}', [EnrollmentController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [EnrollmentController::class, 'restore'])->name('restore');
            Route::delete('/{id}/force', [EnrollmentController::class, 'forceDestroy'])->name('force-destroy');

            Route::post('/{id}/submit', [EnrollmentController::class, 'submit'])->name('submit');
            Route::post('/{id}/verify', [EnrollmentController::class, 'verify'])->name('verify');
            Route::post('/{id}/approve', [EnrollmentController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [EnrollmentController::class, 'reject'])->name('reject');
            Route::post('/{id}/complete', [EnrollmentController::class, 'complete'])->name('complete');
            Route::post('/{id}/uncomplete', [EnrollmentController::class, 'uncomplete'])->name('uncomplete');
            Route::post('/{id}/withdraw', [EnrollmentController::class, 'withdraw'])->name('withdraw');
            Route::post('/{id}/cancel', [EnrollmentController::class, 'cancel'])->name('cancel');
            Route::post('/{id}/transfer', [EnrollmentController::class, 'transfer'])->name('transfer');
            Route::post('/{id}/override-capacity', [EnrollmentController::class, 'overrideCapacity'])->name('override-capacity');

            Route::get('/{id}/requirements', [EnrollmentController::class, 'requirements'])->name('requirements');
            Route::post('/{id}/requirements/sync', [EnrollmentController::class, 'syncRequirements'])->name('requirements-sync');
            Route::patch('/{id}/requirements/{itemId}', [EnrollmentController::class, 'updateRequirementItem'])->name('requirements-update');

            Route::get('/{id}/history', [EnrollmentController::class, 'history'])->name('history');
            Route::get('/{id}/transfers', [EnrollmentController::class, 'transfers'])->name('transfers');
            Route::get('/{id}/signatures', [EnrollmentController::class, 'signatures'])->name('signatures');
            Route::post('/{id}/signatures', [EnrollmentController::class, 'storeSignature'])->name('signatures.store');
            Route::get('/{id}/print', [EnrollmentController::class, 'print'])->name('print');

            Route::get('/{id}/documents', [EnrollmentDocumentController::class, 'index'])->name('documents');
            Route::post('/{id}/documents', [EnrollmentDocumentController::class, 'store'])->name('documents.store');
            Route::get('/{id}/documents/{documentId}/download', [EnrollmentDocumentController::class, 'download'])->name('documents.download');
            Route::get('/{id}/documents/{documentId}/preview', [EnrollmentDocumentController::class, 'preview'])->name('documents.preview');
            Route::delete('/{id}/documents/{documentId}', [EnrollmentDocumentController::class, 'destroy'])->name('documents.destroy');
        });

        $crudRoutes('enrollment-requirements', 'enrollment-requirements', EnrollmentRequirementController::class);

        // Tuition records (per-student fee breakdown, payments and balance).
        $crudRoutes('tuitions', 'tuitions', TuitionController::class);

        // ─────────────────────────────────────────
        // Part 6 – Academic Management
        // ─────────────────────────────────────────
        Route::prefix('academic-operations')->name('academic-operations.')->group(function (): void {
            Route::get('programs', [AcademicOperationsController::class, 'programs'])->name('programs.index');
            Route::post('programs', [AcademicOperationsController::class, 'storeProgram'])
                ->middleware('roles:super-administrator,school-administrator,principal,registrar')
                ->name('programs.store');
            Route::post('programs/{program}/periods', [AcademicOperationsController::class, 'storePeriod'])
                ->middleware('roles:super-administrator,school-administrator,principal,registrar')
                ->name('periods.store');
            Route::post('enrollments/{enrollment}/materialize', [AcademicOperationsController::class, 'materializeEnrollment'])
                ->middleware('roles:super-administrator,school-administrator,principal,registrar')
                ->name('enrollments.materialize');
            Route::post('attendance-sessions', [AcademicOperationsController::class, 'storeAttendanceSession'])
                ->middleware('roles:super-administrator,school-administrator,principal,adviser,teacher')
                ->name('attendance-sessions.store');
            Route::put('attendance-sessions/{attendanceSession}/records', [AcademicOperationsController::class, 'recordAttendance'])
                ->middleware('roles:super-administrator,school-administrator,principal,adviser,teacher')
                ->name('attendance-sessions.records');
            Route::post('attendance-sessions/{attendanceSession}/submit', [AcademicOperationsController::class, 'submitAttendance'])
                ->middleware('roles:super-administrator,school-administrator,principal,adviser,teacher')
                ->name('attendance-sessions.submit');
            Route::post('assessments', [AcademicOperationsController::class, 'storeAssessment'])
                ->middleware('roles:super-administrator,school-administrator,principal,teacher')
                ->name('assessments.store');
            Route::put('assessments/{assessment}/scores', [AcademicOperationsController::class, 'recordScores'])
                ->middleware('roles:super-administrator,school-administrator,principal,teacher')
                ->name('assessments.scores');
            Route::post('enrollments/{enrollment}/promotion', [AcademicOperationsController::class, 'decidePromotion'])
                ->middleware('roles:super-administrator,school-administrator,principal,registrar')
                ->name('promotions.decide');
            Route::get('enrollments/{enrollment}/report-card', [AcademicOperationsController::class, 'reportCard'])
                ->middleware('roles:super-administrator,school-administrator,principal,registrar,adviser,teacher')
                ->name('reports.report-card');
        });

        $crudRoutes('curriculum', 'curriculum', CurriculumEntryController::class);
        $crudRoutes('subject-offerings', 'subject-offerings', SubjectOfferingController::class);

        Route::prefix('teacher-assignments')->name('teacher-assignments.')->group(function (): void {
            Route::get('load', [TeacherAssignmentController::class, 'load'])->name('load');
            Route::get('/', [TeacherAssignmentController::class, 'index'])->name('index');
            Route::post('/', [TeacherAssignmentController::class, 'store'])->name('store');
            Route::get('/{id}', [TeacherAssignmentController::class, 'show'])->name('show');
            Route::put('/{id}', [TeacherAssignmentController::class, 'update'])->name('update');
            Route::patch('/{id}', [TeacherAssignmentController::class, 'update'])->name('partial-update');
            Route::delete('/{id}', [TeacherAssignmentController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [TeacherAssignmentController::class, 'restore'])->name('restore');
            Route::delete('/{id}/force', [TeacherAssignmentController::class, 'forceDestroy'])->name('force-destroy');
        });

        Route::get('academic-settings/grouped', [AcademicSettingController::class, 'grouped'])->name('academic-settings.grouped');
        $crudRoutes('academic-settings', 'academic-settings', AcademicSettingController::class);

        Route::prefix('academic-classes')->name('academic-classes.')->group(function (): void {
            Route::get('/', [AcademicClassController::class, 'index'])->name('index');
            Route::post('/', [AcademicClassController::class, 'store'])->name('store');
            Route::get('/{id}', [AcademicClassController::class, 'show'])->name('show');
            Route::put('/{id}', [AcademicClassController::class, 'update'])->name('update');
            Route::patch('/{id}', [AcademicClassController::class, 'update'])->name('partial-update');
            Route::delete('/{id}', [AcademicClassController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [AcademicClassController::class, 'restore'])->name('restore');
            Route::delete('/{id}/force', [AcademicClassController::class, 'forceDestroy'])->name('force-destroy');

            Route::get('/{id}/members', [AcademicClassController::class, 'members'])->name('members');
            Route::post('/{id}/members', [AcademicClassController::class, 'assignMember'])->name('members.assign');
            Route::delete('/{id}/members/{studentId}', [AcademicClassController::class, 'unassignMember'])->name('members.unassign');
            Route::post('/{id}/members/sync', [AcademicClassController::class, 'syncMembers'])->name('members.sync');
        });

        Route::prefix('schedules')->name('schedules.')->group(function (): void {
            Route::get('timetable', [ClassScheduleController::class, 'timetable'])->name('timetable');
            Route::get('conflicts', [ClassScheduleController::class, 'conflicts'])->name('conflicts');
            Route::get('sections/{sectionId}/timetable', [ClassScheduleController::class, 'sectionTimetable'])->name('sections.timetable');
            Route::get('teachers/{teacherId}/calendar', [ClassScheduleController::class, 'teacherCalendar'])->name('teachers.calendar');

            Route::get('/', [ClassScheduleController::class, 'index'])->name('index');
            Route::post('/', [ClassScheduleController::class, 'store'])->name('store');
            Route::get('/{id}', [ClassScheduleController::class, 'show'])->name('show');
            Route::put('/{id}', [ClassScheduleController::class, 'update'])->name('update');
            Route::patch('/{id}', [ClassScheduleController::class, 'update'])->name('partial-update');
            Route::delete('/{id}', [ClassScheduleController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [ClassScheduleController::class, 'restore'])->name('restore');
            Route::delete('/{id}/force', [ClassScheduleController::class, 'forceDestroy'])->name('force-destroy');
        });

        Route::prefix('grade-scales')->name('grade-scales.')->group(function (): void {
            Route::get('resolve', [GradeScaleController::class, 'resolve'])->name('resolve');
            Route::get('/', [GradeScaleController::class, 'index'])->name('index');
            Route::post('/', [GradeScaleController::class, 'store'])->name('store');
            Route::get('/{id}', [GradeScaleController::class, 'show'])->name('show');
            Route::put('/{id}', [GradeScaleController::class, 'update'])->name('update');
            Route::patch('/{id}', [GradeScaleController::class, 'update'])->name('partial-update');
            Route::delete('/{id}', [GradeScaleController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [GradeScaleController::class, 'restore'])->name('restore');
            Route::delete('/{id}/force', [GradeScaleController::class, 'forceDestroy'])->name('force-destroy');

            Route::get('/{id}/entries', [GradeScaleController::class, 'entries'])->name('entries');
            Route::post('/{id}/entries', [GradeScaleController::class, 'storeEntry'])->name('entries.store');
            Route::put('/{id}/entries/{entryId}', [GradeScaleController::class, 'updateEntry'])->name('entries.update');
            Route::delete('/{id}/entries/{entryId}', [GradeScaleController::class, 'destroyEntry'])->name('entries.destroy');
        });

        Route::prefix('grade-records')->name('grade-records.')->group(function (): void {
            Route::get('students/{studentId}/report', [GradeRecordController::class, 'studentReport'])->name('student-report');
            Route::post('offerings/{offeringId}/bulk', [GradeRecordController::class, 'bulkUpsert'])->name('offerings.bulk');

            Route::get('/', [GradeRecordController::class, 'index'])->name('index');
            Route::post('/', [GradeRecordController::class, 'store'])->name('store');
            Route::get('/{id}', [GradeRecordController::class, 'show'])->name('show');
            Route::put('/{id}', [GradeRecordController::class, 'update'])->name('update');
            Route::patch('/{id}', [GradeRecordController::class, 'update'])->name('partial-update');
            Route::delete('/{id}', [GradeRecordController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [GradeRecordController::class, 'restore'])->name('restore');
            Route::delete('/{id}/force', [GradeRecordController::class, 'forceDestroy'])->name('force-destroy');

            Route::post('/{id}/transition', [GradeRecordController::class, 'transition'])->name('transition');
        });

        Route::prefix('grade-corrections')->name('grade-corrections.')->group(function (): void {
            Route::get('grade-records/{gradeRecordId}/history', [GradeCorrectionController::class, 'historyForGradeRecord'])->name('grade-records.history');

            Route::get('/', [GradeCorrectionController::class, 'index'])->name('index');
            Route::post('/', [GradeCorrectionController::class, 'store'])->name('store');
            Route::get('/{id}', [GradeCorrectionController::class, 'show'])->name('show');
            Route::put('/{id}', [GradeCorrectionController::class, 'update'])->name('update');
            Route::patch('/{id}', [GradeCorrectionController::class, 'update'])->name('partial-update');
            Route::delete('/{id}', [GradeCorrectionController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [GradeCorrectionController::class, 'restore'])->name('restore');
            Route::delete('/{id}/force', [GradeCorrectionController::class, 'forceDestroy'])->name('force-destroy');

            Route::post('/{id}/approve', [GradeCorrectionController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [GradeCorrectionController::class, 'reject'])->name('reject');
        });

        Route::prefix('academic')->name('academic.')->group(function (): void {
            Route::get('dashboard', [AcademicDashboardController::class, 'index'])->name('dashboard');
            Route::get('context', [AcademicDashboardController::class, 'context'])->name('context');
        });

        // ─────────────────────────────────────────
        // Part 8 – Platform & Integration
        // ─────────────────────────────────────────

        // Global (spotlight) search for the navigation bar.
        Route::get('search', GlobalSearchController::class)->name('search');

        // Notification Center (any authenticated user).
        Route::prefix('notifications')->name('notifications.')->group(function (): void {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::get('unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
            Route::patch('read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
            Route::patch('{id}/read', [NotificationController::class, 'markRead'])->name('read');
            Route::delete('read', [NotificationController::class, 'destroyRead'])->name('destroy-read');
        });

        Route::get('notification-preferences', [NotificationController::class, 'preferences'])->name('notification-preferences.show');
        Route::put('notification-preferences', [NotificationController::class, 'updatePreferences'])->name('notification-preferences.update');

        // Portals (parent / student / teacher) – always permission-scoped to self.
        Route::prefix('portal')->name('portal.')->group(function (): void {
            Route::prefix('parent')->name('parent.')->group(function (): void {
                Route::get('dashboard', [ParentPortalController::class, 'dashboard'])->name('dashboard');
                Route::get('children', [ParentPortalController::class, 'children'])->name('children');
                Route::get('children/{id}', [ParentPortalController::class, 'child'])->name('child');
                Route::get('children/{id}/schedule', [ParentPortalController::class, 'childSchedule'])->name('child.schedule');
                Route::get('children/{id}/grades', [ParentPortalController::class, 'childGrades'])->name('child.grades');
                Route::get('children/{id}/attendance', [ParentPortalController::class, 'childAttendance'])->name('child.attendance');
                Route::get('children/{id}/enrollments', [ParentPortalController::class, 'childEnrollments'])->name('child.enrollments');
                Route::get('children/{id}/documents', [ParentPortalController::class, 'childDocuments'])->name('child.documents');
            });

            Route::prefix('student')->name('student.')->group(function (): void {
                Route::get('dashboard', [StudentPortalController::class, 'dashboard'])->name('dashboard');
                Route::get('profile', [StudentPortalController::class, 'profile'])->name('profile');
                Route::get('schedule', [StudentPortalController::class, 'schedule'])->name('schedule');
                Route::get('grades', [StudentPortalController::class, 'grades'])->name('grades');
                Route::get('attendance', [StudentPortalController::class, 'attendance'])->name('attendance');
                Route::get('enrollments', [StudentPortalController::class, 'enrollments'])->name('enrollments');
                Route::get('documents', [StudentPortalController::class, 'documents'])->name('documents');
            });

            Route::prefix('teacher')->name('teacher.')->group(function (): void {
                Route::get('dashboard', [TeacherPortalController::class, 'dashboard'])->name('dashboard');
                Route::get('assignments', [TeacherPortalController::class, 'assignments'])->name('assignments');
                Route::get('schedule', [TeacherPortalController::class, 'schedule'])->name('schedule');
                Route::get('advisory-class', [TeacherPortalController::class, 'advisoryClass'])->name('advisory-class');
                Route::get('sections/{sectionId}/roster', [TeacherPortalController::class, 'classRoster'])->name('sections.roster');
                Route::get('students', [TeacherPortalController::class, 'students'])->name('students');
            });
        });

        // Audit & Activity Center (operators only).
        Route::prefix('activity-logs')->name('activity-logs.')
            ->middleware('roles:super-administrator,school-administrator')
            ->group(function (): void {
                Route::get('/', [ActivityLogController::class, 'index'])->name('index');
                Route::get('stats', [ActivityLogController::class, 'stats'])->name('stats');
                Route::get('catalog', [ActivityLogController::class, 'catalog'])->name('catalog');
                Route::get('causers', [ActivityLogController::class, 'causers'])->name('causers');
                Route::get('{id}', [ActivityLogController::class, 'show'])->name('show');
            });

        // User Management (operators only; policies enforce finer control).
        Route::prefix('users')->name('users.')
            ->middleware('roles:super-administrator,school-administrator')
            ->group(function (): void {
                Route::get('/', [UserManagementController::class, 'index'])->name('index');
                Route::post('/', [UserManagementController::class, 'store'])->name('store');
                Route::get('role-options', [UserManagementController::class, 'roleOptions'])->name('role-options');
                Route::get('{id}', [UserManagementController::class, 'show'])->name('show');
                Route::put('{id}', [UserManagementController::class, 'update'])->name('update');
                Route::patch('{id}', [UserManagementController::class, 'update'])->name('partial-update');
                Route::delete('{id}', [UserManagementController::class, 'destroy'])->name('destroy');
                Route::put('{id}/roles', [UserManagementController::class, 'roles'])->name('roles');
                Route::patch('{id}/active', [UserManagementController::class, 'toggleActive'])->name('toggle-active');
                Route::post('{id}/reset-password', [UserManagementController::class, 'resetPassword'])->name('reset-password');
                Route::post('{id}/impersonate', [UserManagementController::class, 'impersonate'])->name('impersonate');
            });

        // Stop an active impersonation session. Runs with the impersonation
        // token (owned by the impersonated user), so it must stay outside the
        // admin-only users group above.
        Route::post('users/stop-impersonating', [UserManagementController::class, 'stopImpersonating'])
            ->name('users.stop-impersonating');

        // System Settings (grouped / bulk).
        // Grouped endpoints are registered earlier, before the settings CRUD.

        // Admin Dashboard & Analytics.
        Route::get('admin/dashboard', AdminDashboardController::class)
            ->middleware('roles:super-administrator,school-administrator')
            ->name('admin.dashboard');

        // Reports Center (operators only).
        Route::prefix('reports')->name('reports.')
            ->middleware('roles:super-administrator,school-administrator')
            ->group(function (): void {
                Route::get('/', [ReportController::class, 'index'])->name('index');
                Route::post('generate', [ReportController::class, 'generate'])->name('generate');
            });

        // System Health (super administrators only).
        Route::get('system-health', SystemHealthController::class)
            ->middleware('roles:super-administrator')
            ->name('system-health');

        // Backups (super administrators only).
        Route::prefix('backups')->name('backups.')
            ->middleware('roles:super-administrator')
            ->group(function (): void {
                Route::get('/', [BackupController::class, 'index'])->name('index');
                Route::post('/', [BackupController::class, 'store'])->name('store');
                Route::get('{id}/download', [BackupController::class, 'download'])->name('download');
                Route::delete('{id}', [BackupController::class, 'destroy'])->name('destroy');
            });

        // ─────────────────────────────────────────
        // Part 10 – Platform Subscription & License Management
        // ─────────────────────────────────────────
        Route::prefix('platform')->name('platform.')
            ->middleware('roles:super-administrator,platform-administrator')
            ->group(function () use ($crudRoutes): void {
                Route::get('dashboard', [PlatformDashboardController::class, 'index'])->name('dashboard');

                // Feature catalog (registered before tenant feature lookups).
                Route::get('features/catalog', [FeatureController::class, 'catalog'])->name('features.catalog');

                // Tenants.
                $crudRoutes('tenants', 'tenants', TenantController::class);
                Route::post('tenants/{id}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
                Route::post('tenants/{id}/resume', [TenantController::class, 'resume'])->name('tenants.resume');

                // Subscription plans (literal option routes before `{id}`).
                Route::get('plans/options', [SubscriptionPlanController::class, 'options'])->name('plans.options');
                $crudRoutes('plans', 'plans', SubscriptionPlanController::class);

                // Subscriptions (provision registered before `{id}`).
                Route::post('subscriptions/provision', [SubscriptionController::class, 'provision'])->name('subscriptions.provision');
                Route::post('subscriptions/manual-grant', [SubscriptionController::class, 'grant'])->name('subscriptions.manual-grant');
                $crudRoutes('subscriptions', 'subscriptions', SubscriptionController::class);
                Route::post('subscriptions/{id}/renew', [SubscriptionController::class, 'renew'])->name('subscriptions.renew');
                Route::post('subscriptions/{id}/suspend', [SubscriptionController::class, 'suspend'])->name('subscriptions.suspend');
                Route::post('subscriptions/{id}/resume', [SubscriptionController::class, 'resume'])->name('subscriptions.resume');
                Route::post('subscriptions/{id}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
                Route::post('subscriptions/{id}/change-plan', [SubscriptionController::class, 'changePlan'])->name('subscriptions.change-plan');
                Route::post('subscriptions/{id}/features', [SubscriptionController::class, 'toggleFeature'])->name('subscriptions.toggle-feature');
                Route::get('subscriptions/{id}/history', [SubscriptionController::class, 'history'])->name('subscriptions.history');
                Route::get('subscriptions/{id}/features', [FeatureController::class, 'subscription'])->name('subscriptions.features');

                // Billing (invoice generation before `{id}`).
                Route::post('invoices/generate', [InvoiceController::class, 'generate'])->name('invoices.generate');
                $crudRoutes('invoices', 'invoices', InvoiceController::class);
                Route::post('invoices/{id}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
                $crudRoutes('payments', 'payments', PaymentController::class);

                // Licenses.
                $crudRoutes('licenses', 'licenses', LicenseController::class);
                Route::post('licenses/{id}/regenerate', [LicenseController::class, 'regenerate'])->name('licenses.regenerate');
                Route::post('licenses/{id}/revoke', [LicenseController::class, 'revoke'])->name('licenses.revoke');

                // Usage & feature access.
                Route::get('usage', [UsageController::class, 'index'])->name('usage.index');
                Route::get('usage/tenants/{tenantId}', [UsageController::class, 'tenant'])->name('usage.tenant');
                Route::post('usage/tenants/{tenantId}/snapshot', [UsageController::class, 'snapshot'])->name('usage.snapshot');
                Route::get('features/tenants/{tenantId}', [FeatureController::class, 'tenant'])->name('features.tenant');

                // Audit trail (literal actions route before `{id}`).
                Route::get('audit', [AuditController::class, 'index'])->name('audit.index');
                Route::get('audit/actions', [AuditController::class, 'actions'])->name('audit.actions');
                Route::get('audit/{id}', [AuditController::class, 'show'])->name('audit.show');

                // Settings (grouped/bulk before `{id}`).
                Route::get('settings/grouped', [SubscriptionSettingController::class, 'grouped'])->name('settings.grouped');
                Route::put('settings/bulk', [SubscriptionSettingController::class, 'bulk'])->name('settings.bulk');
                $crudRoutes('settings', 'settings', SubscriptionSettingController::class);
            });

        // Read-only subscription summary for the current user's school.
        Route::get('subscription/mine', [SchoolSubscriptionController::class, 'mine'])
            ->name('subscription.mine');
    });
});
