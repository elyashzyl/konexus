<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Database backup history used by the Backup & Maintenance tools.
 *
 * Part 8 – Backup / Maintenance. Only Super Administrators can create,
 * download or delete backups. The file itself is stored on the configured
 * backup disk; this table tracks the metadata and the operator.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('disk')->default('backups');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('status')->default('completed');
            $table->string('type')->default('database');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
