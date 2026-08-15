<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anchor every user to the school (school_profiles) it belongs to.
     *
     * Platform-level roles (super-administrator, platform-administrator) keep a
     * null school; all school roles are required to belong to exactly one school.
     * Email uniqueness becomes per-school instead of global.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_email_unique');

            $table->foreignId('school_profile_id')
                ->nullable()
                ->after('is_active')
                ->constrained('school_profiles')
                ->nullOnDelete();

            $table->unique(['school_profile_id', 'email'], 'users_school_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_school_email_unique');
            $table->dropConstrainedForeignId('school_profile_id');
            $table->string('email')->unique();
        });
    }
};