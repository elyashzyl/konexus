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
        Schema::create('attendance_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_profile_id')->nullable()->constrained('school_profiles')->nullOnDelete();
            $table->foreignId('academic_class_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('status')->default('open');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['academic_class_id', 'attendance_date']);
            $table->index(['school_profile_id', 'attendance_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
