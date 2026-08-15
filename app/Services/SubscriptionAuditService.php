<?php

namespace App\Services;

use App\Enums\Platform\SubscriptionHistoryAction;
use App\Models\Subscription;
use App\Models\SubscriptionHistory;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Request;

/**
 * Writes the subscription-specific audit trail. Every lifecycle event of a
 * tenant or subscription is recorded here (in addition to the generic
 * activity log emitted by the models' LogsActivity trait).
 */
class SubscriptionAuditService
{
    /**
     * Record a subscription/tenant event in the audit trail.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function record(
        Tenant $tenant,
        SubscriptionHistoryAction|string $action,
        array $attributes = [],
    ): SubscriptionHistory {
        $actor = auth()->user();

        return SubscriptionHistory::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $attributes['subscription_id'] ?? null,
            'action' => $action instanceof SubscriptionHistoryAction ? $action->value : $action,
            'description' => $attributes['description'] ?? null,
            'old_value' => $attributes['old_value'] ?? null,
            'new_value' => $attributes['new_value'] ?? null,
            'reason' => $attributes['reason'] ?? null,
            'actor_id' => $attributes['actor_id'] ?? ($actor instanceof User ? $actor->id : null),
            'ip_address' => $attributes['ip_address'] ?? Request::ip(),
        ]);
    }

    /**
     * Record an event scoped to a subscription.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function recordForSubscription(
        Subscription $subscription,
        SubscriptionHistoryAction|string $action,
        array $attributes = [],
    ): SubscriptionHistory {
        return $this->record($subscription->tenant, $action, $attributes + [
            'subscription_id' => $subscription->id,
        ]);
    }

    /**
     * The full audit trail of a tenant (or globally filtered).
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, SubscriptionHistory>
     */
    public function index(array $filters = [], int $perPage = 15)
    {
        $query = SubscriptionHistory::query()
            ->with(['tenant:id,code,name', 'subscription:id,subscription_code']);

        if (! empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (! empty($filters['subscription_id'])) {
            $query->where('subscription_id', $filters['subscription_id']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return $query->latest('id')->paginate($perPage);
    }
}
