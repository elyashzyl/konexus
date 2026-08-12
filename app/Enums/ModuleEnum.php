<?php

namespace App\Enums;

/**
 * The core KONEXUS modules available in Phase 2.
 *
 * Each module exposes a permission namespace (e.g. `section.view`) that will be
 * attached to roles in a later phase. The names are intentionally defined here
 * and nowhere else so that every layer (policies, routes, UI) stays in sync.
 *
 * @phpstan-type PermissionDefinition array{name: string, label: string}
 */
enum ModuleEnum: string
{
    case SYSTEM_SETTINGS = 'system-settings';
    case SCHOOL_PROFILE = 'school';
    case CAMPUS = 'campus';
    case ACADEMIC_YEAR = 'academic-year';
    case ACADEMIC_TERM = 'academic-term';
    case GRADE_LEVEL = 'grade-level';
    case SECTION = 'section';
    case DEPARTMENT = 'department';
    case SUBJECT = 'subject';
    case BUILDING = 'building';
    case ROOM = 'room';
    case SCHOOL_CALENDAR = 'school-calendar';
    case ANNOUNCEMENT = 'announcement';
    case MASTER_DATA = 'master-data';

    // Part 3 – People Management
    case STUDENT = 'student';
    case PARENT = 'parent';
    case GUARDIAN = 'guardian';
    case EMPLOYEE = 'employee';
    case TEACHER = 'teacher';
    case STAFF = 'staff';

    // Part 4 – Enrollment Management
    case ENROLLMENT = 'enrollment';
    case ENROLLMENT_REQUIREMENT = 'enrollment-requirement';
    case ENROLLMENT_DOCUMENT = 'enrollment-document';
    case ENROLLMENT_SIGNATURE = 'enrollment-signature';

    // Part 6 – Academic Management
    case ACADEMIC = 'academic';
    case SUBJECT_OFFERING = 'subject-offering';
    case CURRICULUM = 'curriculum';
    case ACADEMIC_CLASS = 'class';
    case TEACHER_ASSIGNMENT = 'teacher-assignment';
    case SCHEDULE = 'schedule';
    case GRADE = 'grade';
    case GRADE_CORRECTION = 'grade-correction';
    case REPORT_CARD = 'report-card';
    case ACADEMIC_REPORT = 'academic-report';

    /**
     * Human readable label for the module.
     */
    public function label(): string
    {
        return match ($this) {
            self::SYSTEM_SETTINGS => 'System Settings',
            self::SCHOOL_PROFILE => 'School Profile',
            self::CAMPUS => 'Campuses',
            self::ACADEMIC_YEAR => 'Academic Years',
            self::ACADEMIC_TERM => 'Academic Terms',
            self::GRADE_LEVEL => 'Grade Levels',
            self::SECTION => 'Sections',
            self::DEPARTMENT => 'Departments',
            self::SUBJECT => 'Subjects',
            self::BUILDING => 'Buildings',
            self::ROOM => 'Rooms',
            self::SCHOOL_CALENDAR => 'School Calendar',
            self::ANNOUNCEMENT => 'Announcements',
            self::MASTER_DATA => 'Master Data',
            self::STUDENT => 'Students',
            self::PARENT => 'Parents',
            self::GUARDIAN => 'Guardians',
            self::EMPLOYEE => 'Employees',
            self::TEACHER => 'Teachers',
            self::STAFF => 'Staff',
            self::ENROLLMENT => 'Enrollment',
            self::ENROLLMENT_REQUIREMENT => 'Enrollment Requirements',
            self::ENROLLMENT_DOCUMENT => 'Enrollment Documents',
            self::ENROLLMENT_SIGNATURE => 'Enrollment Signatures',
            self::ACADEMIC => 'Academic Management',
            self::SUBJECT_OFFERING => 'Subject Offerings',
            self::CURRICULUM => 'Curriculum',
            self::ACADEMIC_CLASS => 'Classes',
            self::TEACHER_ASSIGNMENT => 'Teacher Assignments',
            self::SCHEDULE => 'Class Schedules',
            self::GRADE => 'Grades',
            self::GRADE_CORRECTION => 'Grade Corrections',
            self::REPORT_CARD => 'Report Cards',
            self::ACADEMIC_REPORT => 'Academic Reports',
        };
    }

    /**
     * Build a namespaced permission name for this module and action.
     */
    public function permission(string $action): string
    {
        return $this->value.'.'.$action;
    }

    /**
     * Every module exposes the same standard set of CRUD actions.
     *
     * @return list<string>
     */
    public static function actions(): array
    {
        return ['view-any', 'view', 'create', 'update', 'delete', 'restore', 'force-delete'];
    }

    /**
     * Additional workflow actions for specific modules.
     *
     * The enrollment module needs its own workflow permissions (verify, approve,
     * reject, withdraw, transfer, …) on top of the standard CRUD surface.
     *
     * @return array<string, list<string>>
     */
    public static function extraActions(): array
    {
        return [
            self::ENROLLMENT->value => [
                'export', 'import', 'print', 'verify', 'approve', 'reject', 'withdraw',
                'transfer', 'complete', 'cancel', 'override-capacity',
                'requirements-view', 'requirements-manage',
                'documents-view', 'documents-upload', 'documents-delete',
                'signatures-view', 'signatures-sign',
            ],
            self::STUDENT->value => [
                'export', 'import', 'print',
                'documents-view', 'documents-upload', 'documents-delete',
                'medical-view', 'medical-update',
                'family-view', 'family-update',
                'history-view', 'timeline-view',
                'status-update', 'qr',
            ],
            self::ACADEMIC->value => [
                'dashboard', 'context', 'export', 'import', 'print',
                'class-override', 'sync-classes',
            ],
            self::SUBJECT_OFFERING->value => [
                'export', 'import', 'print', 'assign-teacher', 'unassign-teacher',
            ],
            self::CURRICULUM->value => [
                'export', 'import', 'manage',
            ],
            self::ACADEMIC_CLASS->value => [
                'export', 'print', 'roster', 'manage-students', 'assign-adviser',
            ],
            self::TEACHER_ASSIGNMENT->value => [
                'view', 'manage',
            ],
            self::SCHEDULE->value => [
                'export', 'print', 'override-conflict',
            ],
            self::GRADE->value => [
                'export', 'import', 'print', 'submit', 'approve', 'publish',
                'return', 'correct', 'entry', 'view-others',
            ],
            self::GRADE_CORRECTION->value => [
                'view', 'request', 'approve', 'reject',
            ],
            self::REPORT_CARD->value => [
                'view', 'generate', 'print',
            ],
            self::ACADEMIC_REPORT->value => [
                'view', 'generate', 'print', 'export',
            ],
        ];
    }

    /**
     * The effective permission actions exposed by a module.
     *
     * @return list<string>
     */
    public static function actionsFor(ModuleEnum $module): array
    {
        return array_values(array_unique([
            ...self::actions(),
            ...(self::extraActions()[$module->value] ?? []),
        ]));
    }

    /**
     * The full permission catalog for every module (used by the seeder and UI).
     *
     * @return list<PermissionDefinition>
     */
    public static function permissionDefinitions(): array
    {
        $definitions = [];

        foreach (self::cases() as $module) {
            foreach (self::actionsFor($module) as $action) {
                $definitions[] = [
                    'name' => $module->permission($action),
                    'label' => ucfirst(ucwords($action, '-')).' '.$module->label(),
                ];
            }
        }

        return $definitions;
    }
}
