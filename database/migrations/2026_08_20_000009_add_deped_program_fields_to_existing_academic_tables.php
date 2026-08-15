<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('curriculum_entries', function (Blueprint $table): void {
            $table->foreignId('curriculum_program_id')->nullable()->after('school_profile_id')->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('weekly_minutes')->nullable()->after('units');
            $table->foreignId('prerequisite_subject_id')->nullable()->after('subject_id')->constrained('subjects')->nullOnDelete();
            $table->json('eligible_clusters')->nullable()->after('subject_type');
            $table->json('assessment_policy')->nullable()->after('eligible_clusters');
            $table->index(['curriculum_program_id', 'grade_level_id']);
        });

        Schema::table('subject_offerings', function (Blueprint $table): void {
            $table->foreignId('curriculum_program_id')->nullable()->after('school_profile_id')->constrained()->nullOnDelete();
            $table->foreignId('curriculum_entry_id')->nullable()->after('subject_id')->constrained()->nullOnDelete();
            $table->index(['curriculum_program_id', 'section_id']);
        });

        Schema::table('enrollments', function (Blueprint $table): void {
            $table->foreignId('curriculum_program_id')->nullable()->after('academic_year_id')->constrained()->nullOnDelete();
            $table->string('program_cluster')->nullable()->after('track');
            $table->json('elective_selections')->nullable()->after('program_cluster');
            $table->index(['curriculum_program_id', 'grade_level_id']);
        });

        Schema::table('grade_records', function (Blueprint $table): void {
            $table->foreignId('academic_period_id')->nullable()->after('academic_term_id')->constrained()->nullOnDelete();
            $table->foreignId('student_subject_enrollment_id')->nullable()->after('student_id')->constrained()->nullOnDelete();
            $table->index(['academic_period_id', 'subject_offering_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grade_records', function (Blueprint $table): void {
            $table->dropForeign(['academic_period_id', 'student_subject_enrollment_id']);
            $table->dropIndex(['academic_period_id', 'subject_offering_id']);
            $table->dropColumn(['academic_period_id', 'student_subject_enrollment_id']);
        });

        Schema::table('enrollments', function (Blueprint $table): void {
            $table->dropForeign(['curriculum_program_id']);
            $table->dropIndex(['curriculum_program_id', 'grade_level_id']);
            $table->dropColumn(['curriculum_program_id', 'program_cluster', 'elective_selections']);
        });

        Schema::table('subject_offerings', function (Blueprint $table): void {
            $table->dropForeign(['curriculum_program_id', 'curriculum_entry_id']);
            $table->dropIndex(['curriculum_program_id', 'section_id']);
            $table->dropColumn(['curriculum_program_id', 'curriculum_entry_id']);
        });

        Schema::table('curriculum_entries', function (Blueprint $table): void {
            $table->dropForeign(['curriculum_program_id', 'prerequisite_subject_id']);
            $table->dropIndex(['curriculum_program_id', 'grade_level_id']);
            $table->dropColumn(['curriculum_program_id', 'weekly_minutes', 'prerequisite_subject_id', 'eligible_clusters', 'assessment_policy']);
        });
    }
};
