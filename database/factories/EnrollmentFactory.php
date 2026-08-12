<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Enrollment>
     */
    protected $model = Enrollment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = AcademicYear::factory()->create();

        return [
            'student_id' => Student::factory(),
            'academic_year_id' => $year->id,
            'academic_term_id' => null,
            'campus_id' => Campus::factory(),
            'grade_level_id' => GradeLevel::factory(),
            'section_id' => null,
            'enrollment_number' => fake()->unique()->numerify('ENR-####-######'),
            'reference_number' => fake()->unique()->numerify('KXN-EN-####-######'),
            'status' => 'draft',
            'enrollment_type' => 'new-student',
            'enrollment_date' => now()->toDateString(),
            'date_enrolled' => null,
            'is_active' => true,
        ];
    }

    /**
     * Tag the enrollment with a section in the same grade level.
     */
    public function inSection(): static
    {
        return $this->afterCreating(function (Enrollment $enrollment): void {
            if ($enrollment->section_id === null) {
                $section = Section::query()
                    ->where('grade_level_id', $enrollment->grade_level_id)
                    ->where('is_active', true)
                    ->first() ?? Section::factory()->create(['grade_level_id' => $enrollment->grade_level_id]);

                $enrollment->forceFill(['section_id' => $section->id])->save();
            }
        });
    }
}