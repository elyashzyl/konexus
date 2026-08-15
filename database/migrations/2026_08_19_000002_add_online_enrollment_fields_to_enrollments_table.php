<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the enrollments table to support the public online enrollment
 * application (Part 1). Pre-enrollment applications do not yet reference a
 * student, campus or grade level, so those columns become nullable and the
 * part-1 fields are stored alongside the enrollment record.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropForeign(['campus_id']);
            $table->dropForeign(['grade_level_id']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->change();
            $table->foreignId('campus_id')->nullable()->change();
            $table->foreignId('grade_level_id')->nullable()->change();
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students')->nullOnDelete();
            $table->foreign('campus_id')->references('id')->on('campuses')->nullOnDelete();
            $table->foreign('grade_level_id')->references('id')->on('grade_levels')->nullOnDelete();

            $table->string('department')->nullable()->after('grade_level_id');
            $table->string('strand')->nullable()->after('department');
            $table->string('track')->nullable()->after('strand');
            $table->string('incoming_level')->nullable()->after('track');
            $table->string('email')->nullable()->after('incoming_level');
            $table->string('mobile_number')->nullable()->after('email');
            $table->timestamp('application_submitted_at')->nullable()->after('mobile_number');
            $table->timestamp('application_expires_at')->nullable()->after('application_submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn([
                'department',
                'strand',
                'track',
                'incoming_level',
                'email',
                'mobile_number',
                'application_submitted_at',
                'application_expires_at',
            ]);

            $table->dropForeign(['student_id']);
            $table->dropForeign(['campus_id']);
            $table->dropForeign(['grade_level_id']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('student_id')->change();
            $table->foreignId('campus_id')->change();
            $table->foreignId('grade_level_id')->change();
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('campus_id')->references('id')->on('campuses')->restrictOnDelete();
            $table->foreign('grade_level_id')->references('id')->on('grade_levels')->restrictOnDelete();
        });
    }
};