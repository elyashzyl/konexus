<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('campus_student', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campus_id')->constrained('campuses')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['campus_id', 'student_id']);
        });

        // Backfill: anchor every existing student to the first active campus
        // of their school so existing students remain visible after scoping.
        $campuses = DB::table('campuses')
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'school_profile_id']);

        $defaults = $campuses->groupBy('school_profile_id')->map->first();

        $students = DB::table('students')
            ->whereNull('deleted_at')
            ->get(['id', 'school_profile_id']);

        $now = now();

        foreach ($students as $student) {
            $campusId = $defaults[$student->school_profile_id]?->id;

            if ($campusId === null) {
                continue;
            }

            DB::table('campus_student')->insert([
                'campus_id' => $campusId,
                'student_id' => $student->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campus_student');
    }
};