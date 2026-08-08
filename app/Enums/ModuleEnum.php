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
     * The full permission catalog for every module (used by the seeder and UI).
     *
     * @return list<PermissionDefinition>
     */
    public static function permissionDefinitions(): array
    {
        $definitions = [];

        foreach (self::cases() as $module) {
            foreach (self::actions() as $action) {
                $definitions[] = [
                    'name' => $module->permission($action),
                    'label' => ucfirst(ucwords($action, '-')).' '.$module->label(),
                ];
            }
        }

        return $definitions;
    }
}
