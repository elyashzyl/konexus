<?php

namespace App\Listeners;

use App\Enums\RoleEnum;
use App\Events\InvoiceOverdue;
use App\Events\SubscriptionCancelled;
use App\Events\SubscriptionExpired;
use App\Events\SubscriptionExpiring;
use App\Events\SubscriptionSuspended;
use App\Models\User;
use App\Notifications\SubscriptionNotification;
use Illuminate\Events\Dispatcher;

/**
 * Notifies platform administrators and the affected tenant's school
 * administrators whenever a subscription lifecycle event fires.
 */
class SubscriptionEventNotifier
{
    /**
     * Register the listeners for this subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(SubscriptionExpired::class, self::class.'@handleExpired');
        $events->listen(SubscriptionExpiring::class, self::class.'@handleExpiring');
        $events->listen(SubscriptionSuspended::class, self::class.'@handleSuspended');
        $events->listen(SubscriptionCancelled::class, self::class.'@handleCancelled');
        $events->listen(InvoiceOverdue::class, self::class.'@handleOverdue');
    }

    public function handleExpired(SubscriptionExpired $event): void
    {
        $this->notify(
            'Subscription expired',
            "Subscription {$event->subscription->subscription_code} for {$this->tenantName($event)} has expired.",
            'subscription',
            ['subscription_id' => $event->subscription->id],
            $event
        );
    }

    public function handleExpiring(SubscriptionExpiring $event): void
    {
        $this->notify(
            'Subscription expiring soon',
            "Subscription {$event->subscription->subscription_code} for {$this->tenantName($event)} expires in {$event->daysRemaining} day(s).",
            'subscription',
            ['subscription_id' => $event->subscription->id, 'days_remaining' => $event->daysRemaining],
            $event
        );
    }

    public function handleSuspended(SubscriptionSuspended $event): void
    {
        $this->notify(
            'Subscription suspended',
            "Subscription {$event->subscription->subscription_code} for {$this->tenantName($event)} has been suspended.",
            'subscription',
            ['subscription_id' => $event->subscription->id],
            $event
        );
    }

    public function handleCancelled(SubscriptionCancelled $event): void
    {
        $this->notify(
            'Subscription cancelled',
            "Subscription {$event->subscription->subscription_code} for {$this->tenantName($event)} has been cancelled.",
            'subscription',
            ['subscription_id' => $event->subscription->id],
            $event
        );
    }

    public function handleOverdue(InvoiceOverdue $event): void
    {
        $this->notify(
            'Invoice overdue',
            "Invoice {$event->invoice->invoice_number} is now overdue.",
            'billing',
            ['invoice_id' => $event->invoice->id],
            $event
        );
    }

    /**
     * Send the notification to every platform administrator.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function notify(string $title, string $body, string $type, array $payload, object $event): void
    {
        User::query()
            ->where('is_active', true)
            ->role([RoleEnum::SUPER_ADMINISTRATOR->roleName(), RoleEnum::PLATFORM_ADMINISTRATOR->roleName()])
            ->get()
            ->each(fn (User $user) => $user->notify(new SubscriptionNotification($title, $body, $type, $payload)));
    }

    /**
     * The tenant name for the event subject.
     */
    protected function tenantName(object $event): string
    {
        $tenant = method_exists($event, 'subscription') && $event->subscription->relationLoaded('tenant')
            ? $event->subscription->tenant
            : $event->subscription->tenant()->first();

        return $tenant?->name ?? 'a tenant';
    }
}