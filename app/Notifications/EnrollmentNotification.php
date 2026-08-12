<?php

namespace App\Notifications;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EnrollmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly Enrollment $enrollment,
        public readonly string $oldStatus,
        public readonly string $newStatus,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

/**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $student = $this->enrollment->student;

        $label = static fn (string $status): string =>
            EnrollmentStatus::tryFrom($status)?->label() ?? ucfirst($status);

        return [
            'title' => 'Enrollment status changed',
            'body' => sprintf(
                '%s (%s) moved from %s to %s',
                $student?->full_name ?: $this->enrollment->enrollment_number,
                $this->enrollment->enrollment_number,
                $label($this->oldStatus),
                $label($this->newStatus),
            ),
            'type' => 'enrollment',
            'enrollment_id' => $this->enrollment->id,
            'enrollment_number' => $this->enrollment->enrollment_number,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
        ];
    }
}