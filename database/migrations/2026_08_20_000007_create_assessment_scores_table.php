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
        Schema::create('assessment_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_profile_id')->nullable()->constrained('school_profiles')->nullOnDelete();
            $table->foreignId('assessment_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_subject_enrollment_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 8, 2)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['assessment_item_id', 'student_subject_enrollment_id'], 'assessment_item_student_unique');
            $table->index(['school_profile_id', 'student_subject_enrollment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_scores');
    }
};
