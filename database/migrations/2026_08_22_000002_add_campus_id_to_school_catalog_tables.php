<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foundation catalog tables that gain a campus anchor.
     *
     * These school-owned catalog records now belong to a campus so that a
     * selected workspace only surfaces its own grade levels, sections,
     * subjects, departments, buildings, rooms, calendar events and
     * announcements.
     *
     * @var list<string>
     */
    private const TABLES = [
        'grade_levels',
        'sections',
        'subjects',
        'departments',
        'buildings',
        'rooms',
        'school_calendar_events',
        'announcements',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
                $table->index('campus_id');
            });
        }

        // Backfill: anchor every existing record to the first active campus of
        // its school so existing catalogs remain visible after scoping.
        $campuses = DB::table('campuses')
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'school_profile_id']);

        $defaults = $campuses->groupBy('school_profile_id')->map->first();

        foreach (self::TABLES as $table) {
            $records = DB::table($table)
                ->whereNull('deleted_at')
                ->whereNull('campus_id')
                ->get(['id', 'school_profile_id']);

            foreach ($records as $record) {
                $campusId = $defaults[$record->school_profile_id]?->id;

                if ($campusId === null) {
                    continue;
                }

                DB::table($table)
                    ->where('id', $record->id)
                    ->update(['campus_id' => $campusId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropForeign(['campus_id']);
                $table->dropIndex(['campus_id']);
                $table->dropColumn('campus_id');
            });
        }
    }
};