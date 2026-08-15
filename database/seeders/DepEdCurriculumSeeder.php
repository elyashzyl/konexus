<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\CurriculumEntry;
use App\Models\CurriculumProgram;
use App\Models\GradeLevel;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class DepEdCurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $year = AcademicYear::query()->where('name', '2026-2027')->first();
        if ($year === null) {
            return;
        }

        $programs = [
            ['name' => 'MATATAG Curriculum Grades 1-9', 'code' => 'MATATAG-G1-9', 'framework' => 'matatag', 'calendar_type' => 'quarterly', 'sequences' => range(1, 9), 'clusters' => null],
            ['name' => 'K to 12 Curriculum Grade 10', 'code' => 'K12-G10', 'framework' => 'k12-2016', 'calendar_type' => 'quarterly', 'sequences' => [10], 'clusters' => null],
            ['name' => 'Strengthened SHS Curriculum', 'code' => 'SSHS-G11-12', 'framework' => 'strengthened-shs', 'calendar_type' => 'semester', 'sequences' => [11, 12], 'clusters' => ['GAS', 'TechPro']],
        ];

        foreach ($programs as $definition) {
            $gradeIds = GradeLevel::query()->whereIn('sequence', $definition['sequences'])->pluck('id')->all();
            $program = CurriculumProgram::query()->firstOrCreate(
                ['academic_year_id' => $year->id, 'code' => $definition['code']],
                ['name' => $definition['name'], 'framework' => $definition['framework'], 'calendar_type' => $definition['calendar_type'], 'grade_level_ids' => $gradeIds, 'clusters' => $definition['clusters'], 'compliance_status' => 'deped-aligned', 'status' => 'active', 'is_active' => true],
            );

            $this->seedPeriods($program);
            $this->seedEntries($program, $gradeIds);
        }
    }

    private function seedPeriods(CurriculumProgram $program): void
    {
        $periods = $program->calendar_type === 'quarterly'
            ? [['Q1', '1st Quarter', '2026-06-01', '2026-08-31'], ['Q2', '2nd Quarter', '2026-09-01', '2026-11-30'], ['Q3', '3rd Quarter', '2026-12-01', '2027-02-28'], ['Q4', '4th Quarter', '2027-03-01', '2027-03-31']]
            : [['S1', '1st Semester', '2026-06-01', '2026-10-31'], ['S2', '2nd Semester', '2026-11-01', '2027-03-31']];

        foreach ($periods as $sequence => [$code, $name, $startDate, $endDate]) {
            AcademicPeriod::query()->firstOrCreate(['curriculum_program_id' => $program->id, 'code' => $code], ['name' => $name, 'sequence' => $sequence + 1, 'start_date' => $startDate, 'end_date' => $endDate, 'status' => 'planned', 'is_active' => true]);
        }
    }

    /** @param list<int> $gradeIds */
    private function seedEntries(CurriculumProgram $program, array $gradeIds): void
    {
        Subject::query()->whereIn('grade_level_id', $gradeIds)->get()->each(function (Subject $subject) use ($program): void {
            CurriculumEntry::query()->firstOrCreate(
                ['curriculum_program_id' => $program->id, 'grade_level_id' => $subject->grade_level_id, 'subject_id' => $subject->id],
                ['academic_year_id' => $program->academic_year_id, 'subject_type' => 'core', 'units' => 1, 'weekly_minutes' => 240, 'is_required' => true, 'display_order' => $subject->id, 'status' => 'active', 'is_active' => true],
            );
        });
    }
}
