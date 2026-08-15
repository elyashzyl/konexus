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
        Schema::create('academic_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_profile_id')->nullable()->constrained('school_profiles')->nullOnDelete();
            $table->foreignId('curriculum_program_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->unsignedTinyInteger('sequence');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('planned');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['curriculum_program_id', 'code']);
            $table->index(['school_profile_id', 'curriculum_program_id', 'sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_periods');
    }
};
