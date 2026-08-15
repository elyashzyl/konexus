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
        Schema::create('subscription_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month')->nullable();
            $table->unsignedBigInteger('students_count')->default(0);
            $table->unsignedBigInteger('users_count')->default(0);
            $table->unsignedBigInteger('staff_count')->default(0);
            $table->unsignedBigInteger('branches_count')->default(0);
            $table->unsignedBigInteger('storage_mb')->default(0);
            $table->unsignedBigInteger('documents_count')->default(0);
            $table->unsignedBigInteger('database_size_mb')->default(0);
            $table->timestamp('captured_at')->useCurrent();
            $table->timestamps();

            $table->unique(['tenant_id', 'period_year', 'period_month'], 'subscription_usage_unique');
            $table->index(['tenant_id', 'period_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_usage');
    }
};