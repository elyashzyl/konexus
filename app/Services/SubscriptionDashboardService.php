<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
/**
 * Aggregated statistics backing the platform subscription dashboard.
 */
class SubscriptionDashboardService
{
    public function __construct(private readonly UsageService $usage) {}

    /**
     * The headline metrics and supporting data of the dashboard.
     *
     * @return array<string, mixed>
     */
    public function dashboard(array $filters = []): array
    {
        $today = Carbon::today();

        $activeStatuses = ['trial', 'active', 'grace_period', 'past_due'];

        $baseTenants = Tenant::query();
        $baseSubs = Subscription::query()->with(['tenant:id,code,name', 'plan:id,name,code']);

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $baseSubs->where('status', $filters['status']);
        }

        $totalTenants = (clone $baseTenants)->count();
        $activeTenants = (clone $baseTenants)->where('status', 'active')->count();
        $suspendedTenants = (clone $baseTenants)->where('status', 'suspended')->count();

        $activeSubs = (clone $baseSubs)->whereIn('status', $activeStatuses);
        $expiring = (clone $baseSubs)
            ->whereIn('status', ['active', 'grace_period'])
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '>=', $today->toDateString())
            ->whereDate('expiration_date', '<=', $today->copy()->addDays($this->expiringDays())->toDateString());

        $trialSubs = (clone $baseSubs)->where('status', 'trial');
        $suspendedSubs = (clone $baseSubs)->where('status', 'suspended');

        $pendingInvoices = SubscriptionInvoice::query()->where('status', 'pending');
        $overdueInvoices = SubscriptionInvoice::query()->where('status', 'overdue');

        $revenueThisMonth = (float) SubscriptionInvoice::query()
            ->where('status', 'paid')
            ->whereMonth('paid_at', $today->month)
            ->whereYear('paid_at', $today->year)
            ->sum('total');

        $revenueUnpaid = SubscriptionInvoice::query()
            ->whereIn('status', ['pending', 'overdue', 'partially_paid'])
            ->withSum(['payments as paid_amount' => fn ($query) => $query->where('status', 'completed')], 'amount')
            ->get()
            ->sum(fn (SubscriptionInvoice $invoice) => max(0.0, (float) $invoice->total - (float) $invoice->paid_amount));

        $planBreakdown = (clone $baseSubs)
            ->whereIn('status', $activeStatuses)
            ->get()
            ->groupBy(fn (Subscription $sub) => $sub->plan?->name ?? 'No Plan')
            ->map(fn ($group) => $group->count())
            ->sortDesc();

        $recentHistory = SubscriptionHistory::query()
            ->with(['tenant:id,code,name'])
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (SubscriptionHistory $history) => [
                'id' => $history->id,
                'action' => $history->action,
                'description' => $history->description,
                'tenant' => $history->tenant ? ['id' => $history->tenant->id, 'name' => $history->tenant->name] : null,
                'created_at' => $history->created_at?->toISOString(),
            ]);

        $expiringSoon = (clone $expiring)->orderBy('expiration_date')->limit(10)->get()
            ->map(fn (Subscription $sub) => [
                'id' => $sub->id,
                'subscription_code' => $sub->subscription_code,
                'status' => $sub->status,
                'expiration_date' => $sub->expiration_date?->toDateString(),
                'days_remaining' => $sub->daysRemaining(),
                'tenant' => $sub->tenant ? ['id' => $sub->tenant->id, 'name' => $sub->tenant->name] : null,
                'plan' => $sub->plan ? ['id' => $sub->plan->id, 'name' => $sub->plan->name] : null,
            ]);

        return [
            'metrics' => [
                'total_tenants' => $totalTenants,
                'active_tenants' => $activeTenants,
                'suspended_tenants' => $suspendedTenants,
                'active_subscriptions' => $activeSubs->count(),
                'trial_subscriptions' => $trialSubs->count(),
                'suspended_subscriptions' => $suspendedSubs->count(),
                'expiring_subscriptions' => $expiring->count(),
                'pending_invoices' => $pendingInvoices->count(),
                'overdue_invoices' => $overdueInvoices->count(),
                'revenue_this_month' => $revenueThisMonth,
                'revenue_unpaid' => max(0.0, $revenueUnpaid),
            ],
            'plan_breakdown' => $planBreakdown,
            'expiring_soon' => $expiringSoon,
            'recent_activity' => $recentHistory,
        ];
    }

    /**
     * The number of days used to flag expiring subscriptions.
     */
    protected function expiringDays(): int
    {
        return (int) app(SubscriptionSettingsService::class)->get('expiring_notice_days', 30);
    }
}