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
            $table->json('siblings')->nullable()->after('application_expires_at');
            $table->string('tuition_plan')->nullable()->after('siblings');
            $table->json('medical_history')->nullable()->after('tuition_plan');
            $table->json('chinese_details')->nullable()->after('medical_history');

            $table->boolean('photo_consent')->nullable()->after('chinese_details');
            $table->boolean('registration_consent')->nullable()->after('photo_consent');
            $table->boolean('credentialing_consent')->nullable()->after('registration_consent');
            $table->boolean('rules_consent')->nullable()->after('credentialing_consent');
            $table->date('date_of_registration')->nullable()->after('rules_consent');
            $table->decimal('initial_payment', 12, 2)->nullable()->after('date_of_registration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn([
                'siblings',
                'tuition_plan',
                'medical_history',
                'chinese_details',
                'photo_consent',
                'registration_consent',
                'credentialing_consent',
                'rules_consent',
                'date_of_registration',
                'initial_payment',
            ]);
        });
    }
};