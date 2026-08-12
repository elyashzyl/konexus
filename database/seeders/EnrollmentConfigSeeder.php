<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

/**
 * Seed the enrollment-related system configuration entries.
 */
class EnrollmentConfigSeeder extends Seeder
{
    /**
     * The default enrollment settings as [group, key, value, type].
     *
     * @var list<array{group: string, key: string, value: string, type: string}>
     */
    protected array $settings = [
        ['enrollment', 'enrollment_number_format', 'ENR-{YEAR}-{SEQ:6}', 'string'],
        ['enrollment', 'reference_number_format', 'KXN-EN-{YEAR}-{SEQ:6}', 'string'],
        ['enrollment', 'allow_multiple_per_year_branch', 'false', 'boolean'],
        ['enrollment', 'auto_assign_section', 'true', 'boolean'],
        ['enrollment', 'require_all_documents', 'true', 'boolean'],
        ['enrollment', 'allow_capacity_override', 'true', 'boolean'],
        ['enrollment', 'transfer_within_school', 'true', 'boolean'],
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
                    'is_public' => false,
                    'sort_order' => $index,
                ]
            );
        }
    }
}