<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seed a couple of starter announcements.
 */
class AnnouncementsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $author = User::query()->first();

        Announcement::query()->firstOrCreate(
            ['title' => 'Welcome to School Year 2026-2027'],
            [
                'content' => 'Welcome back, BPHS! We are excited to begin another school year of learning and growth.',
                'category' => 'general',
                'priority' => 'high',
                'target_audience' => 'all',
                'author_id' => $author?->id,
                'published' => true,
                'published_at' => now(),
                'is_active' => true,
            ]
        );

        Announcement::query()->firstOrCreate(
            ['title' => 'Club and Organization Registration'],
            [
                'content' => 'Registration for student clubs and organizations will open this month. Students may sign up through their advisers.',
                'category' => 'academic',
                'priority' => 'normal',
                'target_audience' => 'students',
                'author_id' => $author?->id,
                'published' => true,
                'published_at' => now(),
                'is_active' => true,
            ]
        );
    }
}
