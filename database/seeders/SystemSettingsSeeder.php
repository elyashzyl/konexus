<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

/**
 * Seed the default system configuration entries.
 */
class SystemSettingsSeeder extends Seeder
{
    /**
     * The default settings as [group, key, value, type].
     *
     * @var list<array{group: string, key: string, value: string, type: string}>
     */
    protected array $settings = [
        ['general', 'school_name', 'Baguio Patriotic High School', 'string'],
        ['general', 'school_short_name', 'BPHS', 'string'],
        ['general', 'academic_calendar_type', 'quarterly', 'string'],
        ['general', 'default_term_label', 'Quarter', 'string'],
        ['general', 'country', 'Philippines', 'string'],
        ['registrar', 'default_grade_level', '', 'string'],
        ['notifications', 'sms_enabled', 'false', 'boolean'],
        ['notifications', 'email_enabled', 'true', 'boolean'],
        ['appearance', 'accent_color', 'indigo', 'string'],
        ['portal', 'portal_enabled', 'true', 'boolean'],
        ['portal', 'parent_registration_enabled', 'true', 'boolean'],
        ['portal', 'student_registration_enabled', 'true', 'boolean'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->settings as $index => [$group, $key, $value, $type]) {
            SystemSetting::query()->firstOrCreate(
                ['group' => $group, 'key' => $key],
                [
                    'value' => $value,
                    'type' => $type,
                    'is_public' => $group === 'general',
                    'sort_order' => $index,
                ]
            );
        }
    }
}
