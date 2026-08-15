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
        Schema::create('curriculum_programs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_profile_id')->nullable()->constrained('school_profiles')->nullOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('framework');
            $table->string('calendar_type');
            $table->json('grade_level_ids');
            $table->json('clusters')->nullable();
            $table->string('compliance_status')->default('deped-aligned');
            $table->string('status')->default('draft');
            $table->text('local_adaptation_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['academic_year_id', 'code'], 'curriculum_programs_year_code_unique');
            $table->index(['school_profile_id', 'academic_year_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_programs');
    }
};
