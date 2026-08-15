<?php

namespace App\Services;

use App\Enums\Platform\PaymentStatus;
use App\Enums\Platform\SubscriptionHistoryAction;
use App\Exceptions\ApiException;
use App\Models\SubscriptionPayment;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Contracts\SubscriptionPaymentRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Records payments against invoices and reconciles invoice status after each
 * completed transaction.
 */
class PaymentService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['reference_number'];

    /**
     * Relation columns included in free-text search.
     *
     * @var array<string, list<string>>
     */
    protected array $searchableRelations = ['tenant' => ['name', 'code']];

    protected array $sortable = ['id', 'amount', 'payment_date', 'status', 'created_at', 'updated_at'];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = ['tenant', 'subscription.plan', 'invoice'];

    protected string $defaultSortBy = 'id';

    protected string $defaultSortDir = 'desc';

    public function __construct(
        private readonly SubscriptionPaymentRepositoryInterface $repo,
        private readonly BillingService $billing,
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

        foreach (['status', 'tenant_id', 'subscription_id', 'invoice_id', 'payment_method'] as $column) {
            if ($request->has($column)) {
                $filters[$column] = $request->input($column);
            }
        }

        return $filters;
    }

    /**
     * Record a payment against an invoice.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        $invoice = \App\Models\SubscriptionInvoice::query()->with('subscription')->findOrFail($data['invoice_id']);

        $payable = \App\Enums\Platform\InvoiceStatus::tryFrom($invoice->status)?->isPayable() ?? false;

        if (! $payable) {
            throw ApiException::conflict('Payments cannot be recorded against this invoice.');
        }

        $data['subscription_id'] = $invoice->subscription_id;
        $data['tenant_id'] = $invoice->tenant_id;
        $data['recorded_by'] = $data['recorded_by'] ?? auth()->id();
        $data['payment_date'] = $data['payment_date'] ?? now()->toDateString();
        $data['status'] = $data['status'] ?? PaymentStatus::COMPLETED->value;

        $payment = DB::transaction(function () use ($data, $invoice): Model {
            $payment = parent::create($data);

            if ($payment->isCompleted()) {
                $this->billing->reconcile($invoice);
            }

            $this->audit->record($invoice->tenant, SubscriptionHistoryAction::PAYMENT_RECORDED, [
                'subscription_id' => $invoice->subscription_id,
                'description' => "Payment of {$payment->amount} recorded against {$invoice->invoice_number}.",
                'new_value' => ['payment_id' => $payment->id, 'status' => $payment->status],
            ]);

            return $payment;
        });

        return $payment;
    }

    /**
     * Block updates to the monetary facts of a payment once recorded.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        if (in_array($model->status, [PaymentStatus::COMPLETED->value, PaymentStatus::REFUNDED->value], true)) {
            foreach (['amount', 'invoice_id', 'status'] as $guarded) {
                unset($data[$guarded]);
            }
        }

        return parent::update($model, $data);
    }
}