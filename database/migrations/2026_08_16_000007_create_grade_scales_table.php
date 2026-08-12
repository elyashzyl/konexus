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
        Schema::create('grade_scale_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_scale_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('remarks')->nullable();
            $table->decimal('min_grade', 5, 2);
            $table->decimal('max_grade', 5, 2);
            $table->boolean('is_passing')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['grade_scale_id', 'sort_order']);
        });

        Schema::create('grade_scales', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->decimal('min_grade', 5, 2)->default(0);
            $table->decimal('max_grade', 5, 2)->default(100);
            $table->decimal('minimum_passing_grade', 5, 2)->default(75);
            $table->unsignedInteger('decimal_precision')->default(2);
            $table->string('rounding_rule')->default('standard');
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_default')->default(false);
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
        Schema::dropIfExists('grade_scales');
        Schema::dropIfExists('grade_scale_entries');
    }
};