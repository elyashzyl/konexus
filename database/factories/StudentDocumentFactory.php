<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentDocument>
 */
class StudentDocumentFactory extends Factory
{
    protected $model = StudentDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'document_type' => $this->faker->randomElement(['birth-certificate', 'report-card', 'good-moral', 'medical-certificate', 'other']),
            'name' => $this->faker->sentence(3),
            'file_path' => 'student-documents/'.$this->faker->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => $this->faker->numberBetween(1024, 5 * 1024 * 1024),
            'status' => \App\Enums\StudentDocumentStatus::SUBMITTED->value,
            'remarks' => null,
            'expires_at' => null,
            'uploaded_by' => User::factory(),
            'verified_by' => null,
            'verified_at' => null,
            'is_active' => true,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (): array => [
            'status' => \App\Enums\StudentDocumentStatus::VERIFIED->value,
            'verified_by' => User::factory(),
            'verified_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => \App\Enums\StudentDocumentStatus::REJECTED->value,
            'remarks' => $this->faker->sentence(),
        ]);
    }
}