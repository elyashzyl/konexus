<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the online enrollment (Part 3 – Family Background) fields to the
 * parents table: the "check if not applicable" flag and the mother's full
 * name when she was single (maiden name).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            $table->boolean('not_applicable')->default(false)->nullable()->after('relationship');
            $table->string('maiden_name')->nullable()->after('not_applicable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            $table->dropColumn(['not_applicable', 'maiden_name']);
        });
    }
};