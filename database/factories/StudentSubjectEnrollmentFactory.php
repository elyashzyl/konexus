<?php

namespace Database\Factories;

use App\Models\CurriculumProgram;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\StudentSubjectEnrollment;
use App\Models\SubjectOffering;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StudentSubjectEnrollment> */
class StudentSubjectEnrollmentFactory extends Factory
{
    protected $model = StudentSubjectEnrollment::class;

    public function definition(): array
    {
        return ['enrollment_id' => Enrollment::factory(), 'student_id' => Student::factory(), 'curriculum_program_id' => CurriculumProgram::factory(), 'subject_offering_id' => SubjectOffering::factory(), 'status' => 'enrolled', 'subject_snapshot' => ['name' => fake()->words(2, true)], 'assessment_policy_snapshot' => ['written-work' => 1]];
    }
}
