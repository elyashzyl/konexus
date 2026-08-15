<?php

namespace App\Services;

use App\Enums\Platform\InvoiceStatus;
use App\Enums\Platform\PaymentMethod;
use App\Enums\Platform\SubscriptionHistoryAction;
use App\Exceptions\ApiException;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Contracts\SubscriptionInvoiceRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Generates and reconciles subscription invoices and records payments against
 * them. Invoice totals are always derived from the subscription amount so the
 * stored values remain internally consistent.
 */
class BillingService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['invoice_number', 'payment_reference'];

    /**
     * Relation columns included in free-text search.
     *
     * @var array<string, list<string>>
     */
    protected array $searchableRelations = ['tenant' => ['name', 'code']];

    protected array $sortable = ['id', 'invoice_number', 'total', 'status', 'due_date', 'paid_at', 'created_at', 'updated_at'];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = ['tenant', 'subscription.plan'];

    protected string $defaultSortBy = 'id';

    protected string $defaultSortDir = 'desc';

    public function __construct(
        private readonly SubscriptionInvoiceRepositoryInterface $repo,
        private readonly SubscriptionAuditService $audit,
    ) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }

    /**
     * The equality filters extracted from the request.
     *
     * @return array<string, mixed>
     */
    protected function filters(\App\Http\Requests\Api\IndexRequest $request): array
    {
        $filters = parent::filters($request);

        foreach (['status', 'tenant_id', 'subscription_id'] as $column) {
            if ($request->has($column)) {
                $filters[$column] = $request->input($column);
            }
        }

        return $filters;
    }

    /**
     * Create an invoice for a subscription covering a billing period.
     *
     * @param  array<string, mixed>  $data
     */
    public function generateInvoice(Subscription $subscription, array $data = []): SubscriptionInvoice
    {
        $currency = $this->auditCurrency($data);
        $amount = $this->resolveAmount($subscription, $data);
        $taxRate = (float) ($data['tax_rate'] ?? 0);
        $discount = (float) ($data['discount'] ?? 0);
        $tax = round($amount * $taxRate / 100, 2);

        $periodStart = isset($data['billing_period_start'])
            ? Carbon::parse($data['billing_period_start'])
            : ($subscription->last_renewed_at ?? $subscription->start_date);

        $invoice = SubscriptionInvoice::query()->create([
            'invoice_number' => $this->generateInvoiceNumber(),
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
            'billing_period_start' => $periodStart->toDateString(),
            'billing_period_end' => isset($data['billing_period_end'])
                ? Carbon::parse($data['billing_period_end'])->toDateString()
                : $this->periodEnd($periodStart, $subscription->billing_cycle),
            'amount' => $amount,
            'discount' => $discount,
            'tax' => $tax,
            'total' => round($amount - $discount + $tax, 2),
            'currency' => $currency,
            'status' => InvoiceStatus::PENDING->value,
            'due_date' => isset($data['due_date'])
                ? Carbon::parse($data['due_date'])->toDateString()
                : Carbon::today()->addDays(15)->toDateString(),
            'notes' => $data['notes'] ?? null,
        ]);

        $this->audit->recordForSubscription($subscription, SubscriptionHistoryAction::INVOICE_CREATED, [
            'description' => "Invoice {$invoice->invoice_number} generated for ".$this->money($invoice->total, $currency).'.',
            'new_value' => ['invoice_id' => $invoice->id, 'total' => (float) $invoice->total],
        ]);

        return $invoice;
    }

    /**
     * Create an invoice directly from validated data.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        $subscription = Subscription::query()->findOrFail($data['subscription_id']);

        $data['tenant_id'] = $subscription->tenant_id;
        $data['invoice_number'] = $data['invoice_number'] ?? $this->generateInvoiceNumber();
        $data['status'] = $data['status'] ?? InvoiceStatus::PENDING->value;

        $amount = (float) ($data['amount'] ?? $subscription->amount);
        $discount = (float) ($data['discount'] ?? 0);
        $tax = (float) ($data['tax'] ?? 0);
        $data['amount'] = $amount;
        $data['discount'] = $discount;
        $data['tax'] = $tax;
        $data['total'] = round($amount - $discount + $tax, 2);

        return parent::create($data);
    }

    /**
     * Mark an invoice as paid and record the payment.
     *
     * @param  array<string, mixed>  $data
     */
    public function markPaid(SubscriptionInvoice $invoice, array $data = []): SubscriptionInvoice
    {
        if ($invoice->isPaid()) {
            throw ApiException::conflict('The invoice is already fully paid.');
        }

        $paidAt = isset($data['paid_at']) ? Carbon::parse($data['paid_at']) : now();

        $invoice->update([
            'status' => InvoiceStatus::PAID->value,
            'paid_at' => $paidAt,
            'payment_reference' => $data['payment_reference'] ?? $invoice->payment_reference,
            'payment_method' => $data['payment_method'] ?? $invoice->payment_method,
        ]);

        $this->audit->record($invoice->tenant, SubscriptionHistoryAction::INVOICE_PAID, [
            'subscription_id' => $invoice->subscription_id,
            'description' => "Invoice {$invoice->invoice_number} marked as paid.",
            'old_value' => ['status' => $invoice->getOriginal('status')],
            'new_value' => ['status' => InvoiceStatus::PAID->value],
        ]);

        return $invoice;
    }

    /**
     * Recompute the status of an invoice from its completed payments.
     */
    public function reconcile(SubscriptionInvoice $invoice): SubscriptionInvoice
    {
        $balance = $invoice->balance();

        if ($balance <= 0) {
            $invoice->update(['status' => InvoiceStatus::PAID->value, 'paid_at' => $invoice->paid_at ?? now()]);

            return $invoice;
        }

        if ($invoice->payments()->where('status', 'completed')->exists()) {
            $invoice->update(['status' => InvoiceStatus::PARTIALLY_PAID->value]);

            return $invoice;
        }

        if ($invoice->due_date && Carbon::today()->gt($invoice->due_date)) {
            $invoice->update(['status' => InvoiceStatus::OVERDUE->value]);
        }

        return $invoice;
    }

    /**
     * The amount charged by an invoice.
     */
    protected function resolveAmount(Subscription $subscription, array $data): float
    {
        return (float) ($data['amount'] ?? $subscription->amount);
    }

    /**
     * The currency configured for the platform.
     */
    protected function auditCurrency(array $data): string
    {
        return (string) ($data['currency'] ?? config('subscription.currency', 'PHP'));
    }

    /**
     * The end of a billing period for the given cycle.
     */
    protected function periodEnd(Carbon $start, string $cycle): string
    {
        return match ($cycle) {
            'annual' => $start->copy()->addYear()->subDay()->toDateString(),
            'custom' => $start->copy()->addDays(89)->toDateString(),
            default => $start->copy()->addMonth()->subDay()->toDateString(),
        };
    }

    /**
     * Generate a unique invoice number.
     */
    public function generateInvoiceNumber(): string
    {
        do {
            $number = 'INV-'.strtoupper(bin2hex(random_bytes(3)));
        } while (SubscriptionInvoice::query()->withTrashed()->where('invoice_number', $number)->exists());

        return $number;
    }

    /**
     * Format a monetary amount.
     */
    protected function money(float $amount, string $currency): string
    {
        return $currency.' '.number_format($amount, 2);
    }
}