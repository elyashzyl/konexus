<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GradeRecord;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradeRecord>
 */
class GradeRecordFactory extends Factory
{
    protected $model = GradeRecord::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'academic_term_id' => null,
            'grade_level_id' => GradeLevel::factory(),
            'section_id' => null,
            'subject_id' => Subject::factory(),
            'subject_offering_id' => null,
            'teacher_id' => Teacher::factory(),
            'raw_grade' => $this->faker->randomFloat(2, 60, 99),
            'final_grade' => null,
            'remarks' => null,
            'status' => 'draft',
            'submitted_by' => null,
            'submitted_at' => null,
            'approved_by' => null,
            'approved_at' => null,
            'published_at' => null,
            'is_active' => true,
        ];
    }
}