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
        Schema::create('student_subject_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_profile_id')->nullable()->constrained('school_profiles')->nullOnDelete();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('curriculum_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('curriculum_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subject_offering_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('enrolled');
            $table->json('subject_snapshot');
            $table->json('assessment_policy_snapshot')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['student_id', 'subject_offering_id'], 'student_subject_offerings_unique');
            $table->index(['school_profile_id', 'enrollment_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_subject_enrollments');
    }
};
