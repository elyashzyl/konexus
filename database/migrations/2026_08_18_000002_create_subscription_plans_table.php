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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('billing_cycle')->default('monthly');
            $table->decimal('monthly_price', 12, 2)->default(0);
            $table->decimal('annual_price', 12, 2)->default(0);
            $table->unsignedSmallInteger('trial_days')->nullable();
            $table->unsignedBigInteger('max_students')->nullable();
            $table->unsignedBigInteger('max_staff')->nullable();
            $table->unsignedBigInteger('max_branches')->nullable();
            $table->unsignedBigInteger('max_users')->nullable();
            $table->unsignedBigInteger('max_storage_mb')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};