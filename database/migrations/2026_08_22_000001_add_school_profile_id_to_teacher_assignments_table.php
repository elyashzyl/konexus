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
        Schema::table('teacher_assignments', function (Blueprint $table): void {
            $table->foreignId('school_profile_id')->nullable()->constrained('school_profiles')->nullOnDelete();
            $table->index('school_profile_id');
        });

        $defaultSchoolId = DB::table('school_profiles')->orderBy('id')->value('id');

        if ($defaultSchoolId !== null) {
            DB::table('teacher_assignments')
                ->whereNull('school_profile_id')
                ->update(['school_profile_id' => $defaultSchoolId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_assignments', function (Blueprint $table): void {
            $table->dropForeign(['school_profile_id']);
            $table->dropIndex(['school_profile_id']);
            $table->dropColumn('school_profile_id');
        });
    }
};
