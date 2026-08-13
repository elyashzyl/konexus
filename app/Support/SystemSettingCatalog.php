<?php

namespace App\Support;

/**
 * The system settings catalog.
 *
 * Part 8 – System Settings. Declares every settings group, its display label,
 * and the settings keys belonging to each group. The registry drives the
 * grouped Settings API and the validation of bulk updates so settings are
 * never hardcoded in the frontend and unknown keys are rejected.
 */
class SystemSettingCatalog
{
    /**
     * The ordered groups with their label and settings.
     *
     * @var array<string, array{label: string, settings: array<string, array{label: string, type: string, options?: list<string>}>}>
     */
    public const GROUPS = [
        'general' => [
            'label' => 'General',
            'settings' => [
                'school_name' => ['label' => 'School Name', 'type' => 'string'],
                'school_short_name' => ['label' => 'School Short Name', 'type' => 'string'],
                'academic_calendar_type' => ['label' => 'Academic Calendar Type', 'type' => 'string', 'options' => ['quarterly', 'semester', 'trimester']],
                'default_term_label' => ['label' => 'Default Term Label', 'type' => 'string'],
                'country' => ['label' => 'Country', 'type' => 'string'],
            ],
        ],
        'registrar' => [
            'label' => 'Registrar',
            'settings' => [
                'default_grade_level' => ['label' => 'Default Grade Level', 'type' => 'string'],
            ],
        ],
        'notifications' => [
            'label' => 'Notifications',
            'settings' => [
                'sms_enabled' => ['label' => 'SMS Notifications', 'type' => 'boolean'],
                'email_enabled' => ['label' => 'Email Notifications', 'type' => 'boolean'],
            ],
        ],
        'appearance' => [
            'label' => 'Appearance',
            'settings' => [
                'accent_color' => ['label' => 'Accent Color', 'type' => 'string', 'options' => ['indigo', 'emerald', 'rose', 'amber', 'sky', 'slate']],
            ],
        ],
        'portal' => [
            'label' => 'Portals',
            'settings' => [
                'portal_enabled' => ['label' => 'Portal Access', 'type' => 'boolean'],
                'parent_registration_enabled' => ['label' => 'Parent Self-Registration', 'type' => 'boolean'],
                'student_registration_enabled' => ['label' => 'Student Self-Registration', 'type' => 'boolean'],
            ],
        ],
    ];

    /**
     * Whether a key belongs to the catalog.
     */
    public static function has(string $key): bool
    {
        foreach (self::GROUPS as $group) {
            if (array_key_exists($key, $group['settings'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * The group a key belongs to, if any.
     */
    public static function groupOf(string $key): ?string
    {
        foreach (self::GROUPS as $group => $definition) {
            if (array_key_exists($key, $definition['settings'])) {
                return $group;
            }
        }

        return null;
    }
}