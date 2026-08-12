<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherAssignment>
 */
class TeacherAssignmentFactory extends Factory
{
    protected $model = TeacherAssignment::class;

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
            'section_id' => null,
            'subject_id' => Subject::factory(),
            'teacher_id' => Teacher::factory(),
            'units' => 3,
            'status' => 'active',
            'is_active' => true,
        ];
    }
}