<?php

namespace App\Events;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EnrollmentStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Enrollment $enrollment,
        public readonly string $oldStatus,
        public readonly ?User $actor = null,
    ) {}
}