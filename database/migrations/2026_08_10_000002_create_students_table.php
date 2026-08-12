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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_number')->unique();
            $table->string('lrn')->nullable()->unique();
            $table->string('school_student_id')->nullable()->unique();
            $table->string('rfid_number')->nullable()->unique();
            $table->string('qr_code')->nullable()->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('extension_name')->nullable();
            $table->string('nickname')->nullable();
            $table->string('gender');
            $table->date('birth_date');
            $table->string('place_of_birth')->nullable();
            $table->string('civil_status')->nullable();
            $table->string('nationality')->nullable();
            $table->string('citizenship')->nullable();
            $table->string('religion')->nullable();
            $table->string('ethnicity')->nullable();
            $table->string('mother_tongue')->nullable();
            $table->string('blood_type')->nullable();
            $table->string('profile_picture_path')->nullable();
            $table->string('status')->default('active');

            $table->string('mobile_number')->nullable();
            $table->string('telephone_number')->nullable();
            $table->string('email')->nullable()->unique();

            $table->string('current_address')->nullable();
            $table->string('current_province')->nullable();
            $table->string('current_city')->nullable();
            $table->string('current_municipality')->nullable();
            $table->string('current_barangay')->nullable();
            $table->string('current_zip_code')->nullable();

            $table->string('permanent_address')->nullable();
            $table->string('permanent_province')->nullable();
            $table->string('permanent_city')->nullable();
            $table->string('permanent_municipality')->nullable();
            $table->string('permanent_barangay')->nullable();
            $table->string('permanent_zip_code')->nullable();

            $table->string('height')->nullable();
            $table->string('weight')->nullable();
            $table->text('medical_conditions')->nullable();
            $table->text('food_allergies')->nullable();
            $table->text('medicine_allergies')->nullable();
            $table->string('preferred_hospital')->nullable();
            $table->text('medical_notes')->nullable();
            $table->text('emergency_medical_notes')->nullable();

            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->string('emergency_contact_mobile')->nullable();
            $table->string('emergency_contact_telephone')->nullable();
            $table->string('emergency_contact_address')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['last_name', 'first_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
