<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\InvoiceRequest;
use App\Http\Resources\SubscriptionInvoiceResource;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends CrudController
{
    protected string $modelClass = SubscriptionInvoice::class;

    protected string $resourceClass = SubscriptionInvoiceResource::class;

    public function __construct(BillingService $service)
    {
        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return InvoiceRequest::class;
    }

    /**
     * The human readable label of the resource.
     */
    protected function resourceLabel(): string
    {
        return 'Invoice';
    }

    /**
     * Generate an invoice for a subscription's billing period.
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subscription_id' => ['required', 'integer', 'exists:subscriptions,id'],
            'billing_period_start' => ['sometimes', 'date'],
            'billing_period_end' => ['sometimes', 'nullable', 'date'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'discount' => ['sometimes', 'numeric', 'min:0'],
            'tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $subscription = Subscription::query()->findOrFail($request->integer('subscription_id'));

        $this->authorize('generate', $subscription);

        return $this->success(
            new SubscriptionInvoiceResource($this->service->generateInvoice($subscription, $validated)),
            'Invoice generated.',
            201
        );
    }

    /**
     * Mark an invoice as paid.
     */
    public function markPaid(Request $request, int $id): JsonResponse
    {
        $invoice = $this->service->find($id);

        $this->authorize('markPaid', $invoice);

        $validated = $request->validate([
            'paid_at' => ['sometimes', 'date'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['sometimes', 'in:card,bank_transfer,cash,gcash,paymaya,other'],
        ]);

        return $this->success(
            new SubscriptionInvoiceResource($this->service->markPaid($invoice, $validated)),
            'Invoice marked as paid.'
        );
    }
}