<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\SubjectOffering;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubjectOffering>
 */
class SubjectOfferingFactory extends Factory
{
    protected $model = SubjectOffering::class;

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
            'department_id' => null,
            'room_id' => null,
            'units' => 3,
            'status' => 'active',
            'is_active' => true,
        ];
    }
}