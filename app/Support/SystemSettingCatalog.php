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
     * The ordered groups with their label, description and settings.
     *
     * @var array<string, array{label: string, description: string, settings: array<string, array{label: string, description: string, type: string, options?: list<array{value: string, label: string}>}>}>
     */
    public const GROUPS = [
        'general' => [
            'label' => 'General',
            'description' => 'Basic information about your school and how its school year is structured.',
            'settings' => [
                'school_name' => [
                    'label' => 'School Name',
                    'description' => 'The official name of your school. Shown on the landing page and printed documents.',
                    'type' => 'string',
                ],
                'school_short_name' => [
                    'label' => 'School Short Name',
                    'description' => 'A short abbreviation (e.g. BPHS) used where space is limited.',
                    'type' => 'string',
                ],
                'academic_calendar_type' => [
                    'label' => 'Academic Calendar Type',
                    'description' => 'How the school year is divided into terms. New academic years are created to match this structure.',
                    'type' => 'string',
                    'options' => [
                        ['value' => 'quarterly', 'label' => 'Quarterly'],
                        ['value' => 'semester', 'label' => 'Semester'],
                        ['value' => 'trimester', 'label' => 'Trimester'],
                    ],
                ],
                'default_term_label' => [
                    'label' => 'Default Term Label',
                    'description' => 'The label used when an academic term has no name of its own (e.g. "Quarter 1").',
                    'type' => 'string',
                ],
                'country' => [
                    'label' => 'Country',
                    'description' => 'The country where your school operates.',
                    'type' => 'string',
                ],
            ],
        ],
        'registrar' => [
            'label' => 'Registrar',
            'description' => 'Defaults used by the registrar when enrolling and registering students.',
            'settings' => [
                'default_grade_level' => [
                    'label' => 'Default Grade Level',
                    'description' => 'The grade level selected by default when a new student is registered.',
                    'type' => 'string',
                ],
            ],
        ],
        'notifications' => [
            'label' => 'Notifications',
            'description' => 'Which notification channels your school uses to reach students and parents.',
            'settings' => [
                'sms_enabled' => [
                    'label' => 'SMS Notifications',
                    'description' => 'Turn SMS messages on or off for the whole school.',
                    'type' => 'boolean',
                ],
                'email_enabled' => [
                    'label' => 'Email Notifications',
                    'description' => 'Turn email notifications on or off for the whole school.',
                    'type' => 'boolean',
                ],
            ],
        ],
        'appearance' => [
            'label' => 'Appearance',
            'description' => 'Visual preferences for your school\u2019s pages.',
            'settings' => [
                'accent_color' => [
                    'label' => 'Accent Color',
                    'description' => 'The accent color used across your school\u2019s pages.',
                    'type' => 'string',
                    'options' => [
                        ['value' => 'indigo', 'label' => 'Indigo'],
                        ['value' => 'emerald', 'label' => 'Emerald'],
                        ['value' => 'rose', 'label' => 'Rose'],
                        ['value' => 'amber', 'label' => 'Amber'],
                        ['value' => 'sky', 'label' => 'Sky'],
                        ['value' => 'slate', 'label' => 'Slate'],
                    ],
                ],
            ],
        ],
        'portal' => [
            'label' => 'Portals',
            'description' => 'Access and registration options for the student and parent portals.',
            'settings' => [
                'portal_enabled' => [
                    'label' => 'Portal Access',
                    'description' => 'Whether students and parents can sign in to the online portals.',
                    'type' => 'boolean',
                ],
                'parent_registration_enabled' => [
                    'label' => 'Parent Self-Registration',
                    'description' => 'Allow parents to create their own portal accounts.',
                    'type' => 'boolean',
                ],
                'student_registration_enabled' => [
                    'label' => 'Student Self-Registration',
                    'description' => 'Allow students to create their own portal accounts.',
                    'type' => 'boolean',
                ],
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