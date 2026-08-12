<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('enrollment_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('transfer_type')->default('within-school');
            $table->string('from_campus_name')->nullable();
            $table->string('from_grade_level_name')->nullable();
            $table->string('from_section_name')->nullable();
            $table->string('to_campus_name')->nullable();
            $table->string('to_grade_level_name')->nullable();
            $table->string('to_section_name')->nullable();
            $table->string('destination')->nullable();
            $table->date('transfer_date');
            $table->text('reason')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_transfers');
    }
};