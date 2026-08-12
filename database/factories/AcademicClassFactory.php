<?php

namespace Database\Factories;

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicClass>
 */
class AcademicClassFactory extends Factory
{
    protected $model = AcademicClass::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'academic_term_id' => null,
            'campus_id' => Campus::factory(),
            'grade_level_id' => GradeLevel::factory(),
            'section_id' => Section::factory(),
            'adviser_teacher_id' => Teacher::factory(),
            'name' => null,
            'status' => 'active',
            'is_active' => true,
        ];
    }
}