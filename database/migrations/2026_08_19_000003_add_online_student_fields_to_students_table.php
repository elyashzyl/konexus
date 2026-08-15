<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the online enrollment (Part 2 – Student Information and Part 3 –
 * Family Background) fields to the students table.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->json('interests')->nullable()->after('mother_tongue');
            $table->boolean('is_indigenous')->default(false)->nullable()->after('interests');
            $table->string('family_monthly_income')->nullable()->after('is_indigenous');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['interests', 'is_indigenous', 'family_monthly_income']);
        });
    }
};