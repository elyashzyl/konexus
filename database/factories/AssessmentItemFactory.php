<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\AssessmentItem;
use App\Models\SubjectOffering;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AssessmentItem> */
class AssessmentItemFactory extends Factory
{
    protected $model = AssessmentItem::class;

    public function definition(): array
    {
        return ['subject_offering_id' => SubjectOffering::factory(), 'academic_period_id' => AcademicPeriod::factory(), 'component' => 'written-work', 'title' => fake()->sentence(3), 'max_score' => 100, 'display_order' => 0, 'status' => 'draft'];
    }
}
