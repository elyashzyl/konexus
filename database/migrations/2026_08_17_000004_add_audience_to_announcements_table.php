<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Announcement audience targeting, workflow state and scheduling.
 *
 * Part 8 – Announcements & Communication. Extends the existing announcements
 * table with structured audience targeting (roles, grade levels, sections,
 * branches) and a publish workflow (draft / scheduled / published / expired /
 * archived) while keeping the original `target_audience` free-text column for
 * backwards compatibility.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->json('audience')->nullable()->after('target_audience');
            $table->string('status')->default('published')->after('audience');
            $table->timestamp('scheduled_at')->nullable()->after('published_at');
            $table->foreignId('created_by')->nullable()->after('author_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn(['audience', 'status', 'scheduled_at']);
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
