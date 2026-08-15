<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anchor system settings to the school they belong to.
     *
     * Settings were previously global; every school in the platform must now
     * have its own values. Existing rows are backfilled to the first active
     * school and the unique constraint becomes per-school.
     */
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->foreignId('school_profile_id')
                ->nullable()
                ->after('id')
                ->constrained('school_profiles')
                ->cascadeOnDelete();
            $table->index('school_profile_id');
        });

        $defaultSchoolId = DB::table('school_profiles')
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        if ($defaultSchoolId !== null) {
            DB::table('system_settings')
                ->whereNull('school_profile_id')
                ->update(['school_profile_id' => $defaultSchoolId]);
        }

        Schema::table('system_settings', function (Blueprint $table): void {
            $table->dropUnique(['group', 'key']);
            $table->unique(['school_profile_id', 'group', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->dropUnique(['school_profile_id', 'group', 'key']);
            $table->unique(['group', 'key']);
            $table->dropForeign(['school_profile_id']);
            $table->dropIndex(['school_profile_id']);
            $table->dropColumn('school_profile_id');
        });
    }
};