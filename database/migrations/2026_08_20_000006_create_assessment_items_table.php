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
        Schema::create('assessment_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_profile_id')->nullable()->constrained('school_profiles')->nullOnDelete();
            $table->foreignId('subject_offering_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_period_id')->constrained()->cascadeOnDelete();
            $table->string('component');
            $table->string('title');
            $table->decimal('max_score', 8, 2);
            $table->unsignedInteger('display_order')->default(0);
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_profile_id', 'subject_offering_id', 'academic_period_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_items');
    }
};
