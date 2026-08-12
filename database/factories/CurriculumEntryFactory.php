<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\CurriculumEntry;
use App\Models\GradeLevel;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CurriculumEntry>
 */
class CurriculumEntryFactory extends Factory
{
    protected $model = CurriculumEntry::class;

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
            'subject_id' => Subject::factory(),
            'subject_type' => 'core',
            'units' => 3,
            'is_required' => true,
            'display_order' => 0,
            'status' => 'active',
            'is_active' => true,
        ];
    }

    public function elective(): static
    {
        return $this->state(fn (): array => [
            'subject_type' => 'elective',
            'is_required' => false,
        ]);
    }
}