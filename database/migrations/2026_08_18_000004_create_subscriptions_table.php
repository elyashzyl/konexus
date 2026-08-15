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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('subscription_code')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->date('start_date');
            $table->date('expiration_date')->nullable();
            $table->date('trial_started_at')->nullable();
            $table->date('trial_ends_at')->nullable();
            $table->string('trial_status')->nullable();
            $table->string('billing_cycle')->default('monthly');
            $table->decimal('amount', 12, 2)->default(0);
            $table->boolean('auto_renewal')->default(true);
            $table->unsignedSmallInteger('grace_days')->default(0);
            $table->date('grace_ends_at')->nullable();
            $table->string('expiration_behavior')->default('grace_period');
            $table->date('last_renewed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspend_reason')->nullable();
            $table->date('expected_resume_at')->nullable();
            $table->timestamp('resumed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index('plan_id');
            $table->index('expiration_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};