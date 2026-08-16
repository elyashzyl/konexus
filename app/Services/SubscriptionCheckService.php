<?php

namespace App\Services;

use App\Enums\Platform\ExpirationBehavior;
use App\Enums\Platform\InvoiceStatus;
use App\Enums\Platform\SubscriptionHistoryAction;
use App\Enums\Platform\SubscriptionStatus;
use App\Events\SubscriptionExpired;
use App\Events\SubscriptionExpiring;
use App\Models\Subscription;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use Illuminate\Support\Carbon;

/**
 * The scheduled maintenance engine. Runs on a schedule (not per-request) and
 * moves subscriptions through expiration, grace and suspension transitions,
 * flags overdue invoices and captures usage snapshots.
 */
class SubscriptionCheckService
{
    public function __construct(
        private readonly SubscriptionAuditService $audit,
        private readonly SubscriptionSettingsService $settings,
        private readonly UsageService $usage,
    ) {}

    /**
     * Run all scheduled checks.
     *
     * @return array<string, int>
     */
    public function run(): array
    {
        $counts = [
            'converted_trials' => $this->convertExpiredTrials(),
            'entered_grace' => $this->enterGracePeriod(),
            'enforced_expired' => $this->enforceExpired(),
            'flagged_overdue' => $this->flagOverdueInvoices(),
            'captured_usage' => $this->captureUsage(),
            'expiring_notices' => $this->dispatchExpiringNotices(),
        ];

        return $counts;
    }

    /**
     * Move trials whose end date has passed to a paid state (or expire them
     * when no paid plan can be assumed).
     */
    protected function convertExpiredTrials(): int
    {
        $expired = Subscription::query()
            ->where('status', SubscriptionStatus::TRIAL->value)
            ->whereNotNull('trial_ends_at')
            ->whereDate('trial_ends_at', '<', Carbon::today()->toDateString())
            ->get();

        $count = 0;

        foreach ($expired as $subscription) {
            $subscription->update([
                'status' => SubscriptionStatus::ACTIVE->value,
                'trial_status' => 'ended',
                'expiration_date' => $subscription->trial_ends_at,
            ]);

            $this->audit->recordForSubscription($subscription, 'trial_converted', [
                'description' => 'Trial period ended.',
                'old_value' => ['status' => SubscriptionStatus::TRIAL->value],
                'new_value' => ['status' => SubscriptionStatus::ACTIVE->value],
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Move active subscriptions past their expiration into a grace period
     * (when the behavior allows it and the grace window has not elapsed).
     */
    protected function enterGracePeriod(): int
    {
        $today = Carbon::today();

        $expired = Subscription::query()
            ->with('tenant:id,school_profile_id')
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '<', $today->toDateString())
            ->get();

        $count = 0;

        foreach ($expired as $subscription) {
            $behavior = $subscription->expiration_behavior ?: ExpirationBehavior::GRACE_PERIOD->value;
            $graceDays = (int) ($subscription->grace_days ?: (int) $this->settings->get('default_grace_days', 7, $subscription->tenant?->school_profile_id));
            $graceEnd = Carbon::parse($subscription->expiration_date)->addDays($graceDays);

            if ($behavior === ExpirationBehavior::GRACE_PERIOD->value && $today->lte($graceEnd)) {
                $subscription->update([
                    'status' => SubscriptionStatus::GRACE_PERIOD->value,
                    'grace_ends_at' => $graceEnd->toDateString(),
                ]);

                $this->audit->recordForSubscription($subscription, SubscriptionHistoryAction::EXPIRED, [
                    'description' => "Subscription entered grace period until {$graceEnd->toDateString()}.",
                    'old_value' => ['status' => SubscriptionStatus::ACTIVE->value],
                    'new_value' => ['status' => SubscriptionStatus::GRACE_PERIOD->value],
                ]);

                $count++;
            }
        }

        return $count;
    }

    /**
     * Enforce the expiration behavior on subscriptions whose grace window has
     * fully elapsed or which do not grant a grace period.
     */
    protected function enforceExpired(): int
    {
        $today = Carbon::today();

        $grace = Subscription::query()
            ->whereIn('status', [SubscriptionStatus::ACTIVE->value, SubscriptionStatus::GRACE_PERIOD->value])
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '<', $today->toDateString())
            ->get();

        $count = 0;

        foreach ($grace as $subscription) {
            $behavior = $subscription->expiration_behavior ?: ExpirationBehavior::GRACE_PERIOD->value;
            $graceEnd = $subscription->grace_ends_at
                ? Carbon::parse($subscription->grace_ends_at)
                : Carbon::parse($subscription->expiration_date);

            if ($today->lte($graceEnd)) {
                continue;
            }

            $nextStatus = $behavior === ExpirationBehavior::SUSPENDED->value
                ? SubscriptionStatus::SUSPENDED->value
                : SubscriptionStatus::EXPIRED->value;

            $subscription->update(['status' => $nextStatus]);

            $this->audit->recordForSubscription($subscription, SubscriptionHistoryAction::EXPIRED, [
                'description' => "Subscription reached its expiration date and is now {$nextStatus}.",
                'old_value' => ['status' => $subscription->getOriginal('status')],
                'new_value' => ['status' => $nextStatus],
            ]);

            SubscriptionExpired::dispatch($subscription);

            $count++;
        }

        return $count;
    }

    /**
     * Flag invoices past their due date as overdue.
     */
    protected function flagOverdueInvoices(): int
    {
        $overdue = SubscriptionInvoice::query()
            ->where('status', InvoiceStatus::PENDING->value)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', Carbon::today()->toDateString())
            ->update(['status' => 'overdue']);

        return $overdue;
    }

    /**
     * Capture the monthly usage snapshot for every active tenant.
     */
    protected function captureUsage(): int
    {
        $tenants = Tenant::query()
            ->where('status', 'active')
            ->whereHas('subscriptions', fn ($query) => $query->whereIn('status', ['trial', 'active', 'grace_period']))
            ->get();

        $count = 0;

        foreach ($tenants as $tenant) {
            $this->usage->snapshot($tenant);
            $count++;
        }

        return $count;
    }

    /**
     * Emit renewal reminders for subscriptions expiring soon. Idempotent per
     * day: a subscription is reminded at most once a day.
     */
    protected function dispatchExpiringNotices(): int
    {
        $today = Carbon::today();

        $expiring = Subscription::query()
            ->with('tenant:id,school_profile_id')
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '>=', $today->toDateString())
            ->get();

        $count = 0;

        foreach ($expiring as $subscription) {
            $noticeDays = (int) $this->settings->get('expiring_notice_days', 30, $subscription->tenant?->school_profile_id);

            if ($subscription->expiration_date?->toDateString() > $today->copy()->addDays($noticeDays)->toDateString()) {
                continue;
            }

            $alreadyNotified = SubscriptionHistory::query()
                ->where('subscription_id', $subscription->id)
                ->where('action', 'renewal_reminder')
                ->whereDate('created_at', $today->toDateString())
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            SubscriptionExpiring::dispatch($subscription, $subscription->daysRemaining());

            $this->audit->recordForSubscription($subscription, 'renewal_reminder', [
                'description' => 'Renewal reminder sent.',
                'new_value' => ['days_remaining' => $subscription->daysRemaining()],
            ]);

            $count++;
        }

        return $count;
    }
}
