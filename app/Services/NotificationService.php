<?php

namespace App\Services;

use App\Models\NotificationPreference;
use App\Models\Student;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;

/**
 * Centralized delivery of in-app notifications.
 *
 * Part 8 – Notification Center. Every notification created through this
 * service is stored in the standard `notifications` table and checked against
 * the user's channel/category preferences. Email delivery remains opt-in and
 * only happens when a configured mail transport is available.
 */
class NotificationService
{
    /**
     * The categories a user can mute.
     *
     * @var list<string>
     */
    public const CATEGORIES = [
        'enrollment',
        'academic',
        'attendance',
        'finance',
        'library',
        'clinic',
        'guidance',
        'announcement',
        'system',
    ];

    /**
     * The delivery channels a user can toggle per category.
     *
     * @var list<string>
     */
    public const CHANNELS = ['database', 'email'];

    /**
     * Send a notification to a user while honoring their preferences.
     *
     * @param  array<string, mixed>  $data  Serialized notification payload.
     */
    public function send(User $user, string $type, array $data, array $channels = ['database']): void
    {
        if (! $user->is_active) {
            return;
        }

        $category = (string) ($data['category'] ?? $type);
        $allowed = array_values(array_filter($channels, fn (string $channel) => $this->channelEnabled($user, $category, $channel)));

        if ($allowed === []) {
            return;
        }

        $user->notify(new class($type, $data) extends \Illuminate\Notifications\Notification
        {
            public function __construct(
                private readonly string $type,
                private readonly array $data,
            ) {}

            public function via(object $notifiable): array
            {
                return ['database'];
            }

            public function toArray(object $notifiable): array
            {
                return array_merge(['type' => $this->type], $this->data);
            }
        });
    }

    /**
     * Send a notification to the user accounts linked to a student
     * (the student's own account and all linked parent accounts).
     *
     * @param  array<string, mixed>  $data
     */
    public function sendToStudentCircle(Student $student, string $type, array $data, array $channels = ['database']): void
    {
        $data['student_id'] = $student->id;

        $recipients = collect();

        if ($student->relationLoaded('user') ? $student->user : $student->user()->first()) {
            $recipients->push($student->user);
        }

        $parents = $student->relationLoaded('parents') ? $student->parents : $student->parents()->get();
        $recipients = $recipients->merge($parents->pluck('user')->filter());

        foreach ($recipients->unique('id') as $recipient) {
            if ($recipient instanceof User) {
                $this->send($recipient, $type, $data, $channels);
            }
        }
    }

    /**
     * Whether a channel is enabled for a user and category.
     */
    protected function channelEnabled(User $user, string $category, string $channel): bool
    {
        if ($channel === 'email' && ! $this->emailAvailable()) {
            return false;
        }

        $preference = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('category', $category)
            ->where('channel', $channel)
            ->first();

        return $preference === null || (bool) $preference->enabled;
    }

    /**
     * Email delivery is only attempted when a real transport is configured.
     */
    protected function emailAvailable(): bool
    {
        return config('mail.default') !== 'log' && config('mail.default') !== 'array' && filled(config('mail.from.address'));
    }

    /**
     * Persist the preference matrix for a user (full replace).
     *
     * @param  array<string, array<string, bool>>  $matrix  category => channel => enabled
     */
    public function updatePreferences(User $user, array $matrix): void
    {
        DB::transaction(function () use ($user, $matrix): void {
            NotificationPreference::query()->where('user_id', $user->id)->delete();

            foreach ($matrix as $category => $channels) {
                if (! is_array($channels)) {
                    continue;
                }

                foreach ($channels as $channel => $enabled) {
                    if (! in_array($category, self::CATEGORIES, true) || ! in_array($channel, self::CHANNELS, true)) {
                        continue;
                    }

                    NotificationPreference::query()->create([
                        'user_id' => $user->id,
                        'category' => $category,
                        'channel' => $channel,
                        'enabled' => (bool) $enabled,
                    ]);
                }
            }
        });
    }

    /**
     * The preference matrix of a user (defaults to enabled).
     *
     * @return array<string, array<string, bool>>
     */
    public function preferences(User $user): array
    {
        $rows = NotificationPreference::query()->where('user_id', $user->id)->get();

        $matrix = [];
        foreach (self::CATEGORIES as $category) {
            $matrix[$category] = [];
            foreach (self::CHANNELS as $channel) {
                $row = $rows->firstWhere(fn ($r) => $r->category === $category && $r->channel === $channel);
                $matrix[$category][$channel] = $row === null ? true : (bool) $row->enabled;
            }
        }

        return $matrix;
    }
}