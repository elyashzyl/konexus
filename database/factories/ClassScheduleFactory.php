<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSchedule>
 */
class ClassScheduleFactory extends Factory
{
    protected $model = ClassSchedule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'academic_term_id' => null,
            'campus_id' => null,
            'grade_level_id' => GradeLevel::factory(),
            'section_id' => Section::factory(),
            'subject_offering_id' => null,
            'subject_id' => Subject::factory(),
            'teacher_id' => Teacher::factory(),
            'room_id' => null,
            'day' => $this->faker->randomElement(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'conflict_override' => false,
            'conflict_reason' => null,
            'status' => 'active',
            'is_active' => true,
        ];
    }
}