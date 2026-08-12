<?php

namespace Database\Factories;

use App\Models\AcademicClass;
use App\Models\AcademicClassStudent;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicClassStudent>
 */
class AcademicClassStudentFactory extends Factory
{
    protected $model = AcademicClassStudent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_class_id' => AcademicClass::factory(),
            'student_id' => Student::factory(),
            'enrollment_id' => null,
            'source' => AcademicClassStudent::SOURCE_ENROLLMENT,
            'academic_status' => null,
            'remarks' => null,
            'is_active' => true,
        ];
    }
}