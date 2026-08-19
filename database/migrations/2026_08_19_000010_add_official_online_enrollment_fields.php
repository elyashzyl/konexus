<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->boolean('is_withdrawn_student')->default(false)->after('withdrawn_at');
            $table->boolean('is_sanctioned')->default(false)->after('is_withdrawn_student');
            $table->boolean('is_officially_enrolled')->default(false)->after('is_sanctioned');
            $table->string('initial_payment_status')->nullable()->after('initial_payment');
            $table->boolean('online_photo_sharing')->nullable()->after('photo_consent');
            $table->boolean('mother_confirmation')->nullable()->after('rules_consent');
            $table->boolean('father_confirmation')->nullable()->after('mother_confirmation');
            $table->json('account_settings')->nullable()->after('chinese_details');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn([
                'is_withdrawn_student',
                'is_sanctioned',
                'is_officially_enrolled',
                'initial_payment_status',
                'online_photo_sharing',
                'mother_confirmation',
                'father_confirmation',
                'account_settings',
            ]);
        });
    }
};
