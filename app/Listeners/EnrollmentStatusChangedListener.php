<?php

namespace App\Listeners;

use App\Enums\EnrollmentStatus;
use App\Enums\RoleEnum;
use App\Events\EnrollmentStatusChanged;
use App\Models\User;
use App\Notifications\EnrollmentNotification;
use App\Services\NotificationService;

/**
 * Notify the registrar, principal and school administrators plus the student's
 * linked accounts (student + parents) every time the workflow of an enrollment
 * moves from one status to another.
 */
class EnrollmentStatusChangedListener
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(EnrollmentStatusChanged $event): void
    {
        $roles = [
            RoleEnum::SUPER_ADMINISTRATOR->roleName(),
            RoleEnum::SCHOOL_ADMINISTRATOR->roleName(),
            RoleEnum::REGISTRAR->roleName(),
            RoleEnum::PRINCIPAL->roleName(),
        ];

        $subscribers = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', $roles))
            ->get();

        $notification = static fn (User $subscriber) => $subscriber->notify(
            new EnrollmentNotification($event->enrollment, $event->oldStatus, $event->enrollment->status)
        );

        $subscribers->each($notification);

        // Notify the student and their linked parents about the outcome.
        // Drafts may be submitted before any student record exists, so skip
        // the student circle when there is nothing to notify.
        if ($event->enrollment->student === null) {
            return;
        }

        $this->notifications->sendToStudentCircle(
            $event->enrollment->student,
            'enrollment',
            [
                'category' => 'enrollment',
                'title' => 'Enrollment update',
                'body' => sprintf(
                    'Enrollment %s is now %s.',
                    $event->enrollment->enrollment_number,
                    EnrollmentStatus::tryFrom($event->enrollment->status)?->label() ?? $event->enrollment->status,
                ),
                'enrollment_id' => $event->enrollment->id,
                'enrollment_number' => $event->enrollment->enrollment_number,
                'new_status' => $event->enrollment->status,
            ]
        );
    }
}