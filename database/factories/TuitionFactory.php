<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\Tuition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tuition>
 */
class TuitionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Tuition>
     */
    protected $model = Tuition::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tuitionFee = fake()->randomFloat(2, 5000, 50000);
        $miscFee = fake()->randomFloat(2, 0, 10000);
        $otherFees = fake()->randomFloat(2, 0, 5000);
        $discount = fake()->randomFloat(2, 0, 5000);
        $amountPaid = fake()->randomFloat(2, 0, 50000);

        $total = round($tuitionFee + $miscFee + $otherFees - $discount, 2);
        $balance = round($total - $amountPaid, 2);

        return [
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'reference_number' => fake()->unique()->numerify('TUIT-'.now()->format('Y').'-######'),
            'tuition_fee' => $tuitionFee,
            'misc_fee' => $miscFee,
            'other_fees' => $otherFees,
            'discount' => $discount,
            'total' => max(0, $total),
            'amount_paid' => $amountPaid,
            'balance' => max(0, $balance),
            'status' => $amountPaid > 0 ? 'partial' : 'unpaid',
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}