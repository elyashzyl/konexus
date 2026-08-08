<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\SchoolCalendarEvent;
use Illuminate\Database\Seeder;

/**
 * Seed the default school calendar events for the current academic year.
 */
class SchoolCalendarSeeder extends Seeder
{
    /**
     * The default calendar events.
     *
     * @var list<array{title: string, category: string, start_date: string, end_date: string|null, description: string}>
     */
    protected array $events = [
        ['title' => 'Enrollment Period', 'category' => 'enrollment', 'start_date' => '2026-05-04', 'end_date' => '2026-05-29', 'description' => 'Enrollment for School Year 2026-2027'],
        ['title' => 'First Day of Classes', 'category' => 'school-event', 'start_date' => '2026-06-01', 'end_date' => null, 'description' => 'Official start of School Year 2026-2027'],
        ['title' => 'National Heroes Day', 'category' => 'holiday', 'start_date' => '2026-08-31', 'end_date' => null, 'description' => 'Regular holiday'],
        ['title' => '1st Quarter Examinations', 'category' => 'examination', 'start_date' => '2026-08-24', 'end_date' => '2026-08-28', 'description' => 'First quarterly assessment'],
        ['title' => 'Foundation Day', 'category' => 'school-event', 'start_date' => '2026-10-10', 'end_date' => null, 'description' => 'Anniversary celebration of the school'],
        ['title' => 'All Saints\' Day', 'category' => 'holiday', 'start_date' => '2026-11-01', 'end_date' => '2026-11-02', 'description' => 'Regular holidays'],
        ['title' => 'Christmas Break', 'category' => 'holiday', 'start_date' => '2026-12-21', 'end_date' => '2027-01-03', 'description' => 'Christmas vacation'],
        ['title' => 'Baguio Flower Festival (Panagbenga)', 'category' => 'announcement', 'start_date' => '2027-02-01', 'end_date' => '2027-03-05', 'description' => 'City-wide celebration'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $year = AcademicYear::query()->where('name', '2026-2027')->first();

        foreach ($this->events as $event) {
            SchoolCalendarEvent::query()->firstOrCreate(
                ['title' => $event['title'], 'start_date' => $event['start_date']],
                [
                    'category' => $event['category'],
                    'description' => $event['description'],
                    'academic_year_id' => $year?->id,
                    'end_date' => $event['end_date'],
                    'all_day' => true,
                    'is_active' => true,
                ]
            );
        }
    }
}
