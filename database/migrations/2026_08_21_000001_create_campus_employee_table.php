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
        Schema::create('campus_employee', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campus_id')->constrained('campuses')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['campus_id', 'employee_id']);
        });

        // Backfill: anchor every existing employee to the first active campus
        // of their school so existing people remain visible after scoping.
        $campuses = DB::table('campuses')
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'school_profile_id']);

        $defaults = $campuses->groupBy('school_profile_id')->map->first();

        $employees = DB::table('employees')
            ->whereNull('deleted_at')
            ->get(['id', 'school_profile_id']);

        $now = now();

        foreach ($employees as $employee) {
            $campusId = $defaults[$employee->school_profile_id]?->id;

            if ($campusId === null) {
                continue;
            }

            DB::table('campus_employee')->insert([
                'campus_id' => $campusId,
                'employee_id' => $employee->id,
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
        Schema::dropIfExists('campus_employee');
    }
};
