<?php

namespace App\Listeners;

use App\Enums\EnrollmentStatus;
use App\Enums\RoleEnum;
use App\Events\EnrollmentStatusChanged;
use App\Models\User;
use App\Notifications\EnrollmentNotification;

/**
 * Notify the registrar, principal and school administrators every time the
 * workflow of an enrollment moves from one status to another.
 */
class EnrollmentStatusChangedListener
{
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

        foreach ($subscribers as $subscriber) {
            $subscriber->notify(
                new EnrollmentNotification($event->enrollment, $event->oldStatus, $event->enrollment->status)
            );
        }
    }
}