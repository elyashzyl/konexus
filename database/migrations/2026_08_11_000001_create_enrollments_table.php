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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_term_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campus_id')->constrained()->restrictOnDelete();
            $table->foreignId('grade_level_id')->constrained()->restrictOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();

            $table->string('enrollment_number')->unique();
            $table->string('reference_number')->unique();
            $table->string('status')->default('draft');
            $table->string('enrollment_type')->default('new-student');
            $table->date('enrollment_date')->nullable();
            $table->date('date_enrolled')->nullable();

            $table->date('transfer_date')->nullable();
            $table->string('transfer_type')->nullable();
            $table->string('transfer_destination')->nullable();
            $table->string('transfer_destination_school')->nullable();
            $table->text('transfer_reason')->nullable();
            $table->text('transfer_remarks')->nullable();

            $table->string('payment_status')->nullable();
            $table->decimal('down_payment', 12, 2)->nullable();
            $table->date('payment_schedule_date')->nullable();
            $table->string('payment_schedule_details')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('withdrawn_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('withdrawn_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status']);
            $table->index(['student_id', 'status']);
            $table->index(['academic_year_id', 'section_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};