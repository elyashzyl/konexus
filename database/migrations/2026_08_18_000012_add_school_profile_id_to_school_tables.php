<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * School-owned module tables that gain a school profile anchor.
     *
     * `users`, `campuses` and `tenants` already carry `school_profile_id` and
     * pivot tables are excluded (they are only reached through scoped parents).
     *
     * @var list<string>
     */
    private const TABLES = [
        'academic_classes',
        'academic_settings',
        'academic_terms',
        'academic_years',
        'announcements',
        'buildings',
        'class_schedules',
        'curriculum_entries',
        'departments',
        'employees',
        'enrollment_capacity_overrides',
        'enrollment_documents',
        'enrollment_requirement_items',
        'enrollment_requirements',
        'enrollment_signatures',
        'enrollment_transfers',
        'enrollments',
        'grade_corrections',
        'grade_levels',
        'grade_records',
        'grade_scale_entries',
        'grade_scales',
        'guardians',
        'master_data',
        'parents',
        'rooms',
        'school_calendar_events',
        'sections',
        'staff',
        'student_documents',
        'students',
        'subject_offerings',
        'subjects',
        'teachers',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->foreignId('school_profile_id')->nullable()->constrained('school_profiles')->nullOnDelete();
                $table->index('school_profile_id');
            });
        }

        $defaultSchoolId = DB::table('school_profiles')->orderBy('id')->value('id');

        if ($defaultSchoolId === null) {
            return;
        }

        foreach (self::TABLES as $table) {
            DB::table($table)->whereNull('school_profile_id')->update(['school_profile_id' => $defaultSchoolId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropForeign(['school_profile_id']);
                $table->dropIndex(['school_profile_id']);
                $table->dropColumn('school_profile_id');
            });
        }
    }
};