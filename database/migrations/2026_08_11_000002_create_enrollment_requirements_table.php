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
        Schema::create('enrollment_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(true);
            $table->string('type')->nullable();
            $table->json('applicable_grade_levels')->nullable();
            $table->json('applicable_enrollment_types')->nullable();
            $table->foreignId('applicable_academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->string('applicable_campus_ids')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_requirements');
    }
};