<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anchor subscription settings to the workspace they belong to.
     *
     * Settings were previously global; every school in the platform must now
     * have its own values. Existing rows are backfilled to the first active
     * school and the unique constraint becomes per-school.
     */
    public function up(): void
    {
        Schema::table('subscription_settings', function (Blueprint $table): void {
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
            DB::table('subscription_settings')
                ->whereNull('school_profile_id')
                ->update(['school_profile_id' => $defaultSchoolId]);
        }

        Schema::table('subscription_settings', function (Blueprint $table): void {
            $table->dropUnique('subscription_settings_key_unique');
            $table->unique(['school_profile_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_settings', function (Blueprint $table): void {
            $table->dropUnique(['school_profile_id', 'key']);
            $table->unique('key');
            $table->dropForeign(['school_profile_id']);
            $table->dropIndex(['school_profile_id']);
            $table->dropColumn('school_profile_id');
        });
    }
};
