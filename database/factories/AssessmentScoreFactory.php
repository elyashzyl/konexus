<?php

namespace Database\Factories;

use App\Models\AssessmentItem;
use App\Models\AssessmentScore;
use App\Models\StudentSubjectEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AssessmentScore> */
class AssessmentScoreFactory extends Factory
{
    protected $model = AssessmentScore::class;

    public function definition(): array
    {
        return ['assessment_item_id' => AssessmentItem::factory(), 'student_subject_enrollment_id' => StudentSubjectEnrollment::factory(), 'score' => fake()->numberBetween(0, 100)];
    }
}
