<?php

namespace Database\Seeders;

use App\Enums\EnrollmentStatus;
use App\Enums\RequirementItemStatus;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Enrollment;
use App\Models\EnrollmentRequirement;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seed sample enrollment requirements and a handful of enrollment records so
 * the enrollment screens have realistic data to render.
 */
class EnrollmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedRequirements();

        $year = AcademicYear::query()->where('is_active', true)->first()
            ?? AcademicYear::query()->first();

        if (! $year) {
            return;
        }

        $students = Student::query()->where('is_active', true)->take(6)->get();

        $sections = Section::query()->where('is_active', true)->get();
        $campus = Campus::query()->where('is_active', true)->first() ?? Campus::query()->first();

        if ($sections->isEmpty() || ! $campus) {
            return;
        }

        $sequence = 1;

        foreach ($students as $student) {
            $section = $sections->random();

            $enrollment = Enrollment::query()->firstOrCreate(
                ['reference_number' => 'KXN-EN-'.Str::upper((string) $year->code).'-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT)],
                [
                    'student_id' => $student->id,
                    'academic_year_id' => $year->id,
                    'campus_id' => $campus->id,
                    'grade_level_id' => $section->grade_level_id,
                    'section_id' => $section->id,
                    'enrollment_number' => 'ENR-'.Str::upper((string) $year->code).'-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
                    'status' => EnrollmentStatus::OFFICIALLY_ENROLLED->value,
                    'enrollment_type' => $sequence % 3 === 0 ? 'transferee' : 'continuing',
                    'enrollment_date' => now()->subDays(rand(5, 40))->toDateString(),
                    'date_enrolled' => now()->subDays(rand(1, 30))->toDateString(),
                    'is_active' => true,
                ]
            );

            $this->attachRequirementItems($enrollment);

            $sequence++;
        }
    }

    /**
     * Seed the default requirement catalog.
     */
    protected function seedRequirements(): void
    {
        $requirements = [
            ['PSA Birth Certificate', 'PSA-BC', true, ['new-student', 'transferee']],
            ['Report Card', 'REP-CARD', true, null],
            ['Certificate of Good Moral Character', 'GMC', false, null],
            ['Proof of Payment', 'POF-PAY', true, null],
            ['2x2 ID Picture', 'ID-PIC', false, null],
        ];

        foreach ($requirements as $index => [$name, $code, $isRequired, $types]) {
            EnrollmentRequirement::query()->firstOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'description' => 'Default requirement for enrollment.',
                    'is_required' => $isRequired,
                    'applicable_enrollment_types' => $types,
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Attach the applicable requirement items and satisfy them so the seeded
     * enrollments look officially enrolled.
     */
    protected function attachRequirementItems(Enrollment $enrollment): void
    {
        foreach (EnrollmentRequirement::query()->where('is_active', true)->get() as $requirement) {
            if (! $requirement->appliesTo($enrollment)) {
                continue;
            }

            $enrollment->requirementItems()->firstOrCreate(
                ['enrollment_requirement_id' => $requirement->id],
                ['status' => RequirementItemStatus::VERIFIED->value]
            );
        }
    }
}