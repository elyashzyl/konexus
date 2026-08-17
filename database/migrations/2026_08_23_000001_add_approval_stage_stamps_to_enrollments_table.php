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
        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('principal_approved_by')->nullable()->after('cancellation_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('principal_approved_at')->nullable()->after('principal_approved_by');
            $table->foreignId('registrar_reviewed_by')->nullable()->after('principal_approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('registrar_reviewed_at')->nullable()->after('registrar_reviewed_by');
            $table->foreignId('payment_recorded_by')->nullable()->after('registrar_reviewed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('payment_recorded_at')->nullable()->after('payment_recorded_by');
            $table->foreignId('final_checked_by')->nullable()->after('payment_recorded_at')->constrained('users')->nullOnDelete();
            $table->timestamp('final_checked_at')->nullable()->after('final_checked_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('final_checked_by');
            $table->dropColumn('final_checked_at');
            $table->dropConstrainedForeignId('payment_recorded_by');
            $table->dropColumn('payment_recorded_at');
            $table->dropConstrainedForeignId('registrar_reviewed_by');
            $table->dropColumn('registrar_reviewed_at');
            $table->dropConstrainedForeignId('principal_approved_by');
            $table->dropColumn('principal_approved_at');
        });
    }
};