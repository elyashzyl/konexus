<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\PaymentRequest;
use App\Http\Resources\SubscriptionPaymentResource;
use App\Models\SubscriptionPayment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends CrudController
{
    protected string $modelClass = SubscriptionPayment::class;

    protected string $resourceClass = SubscriptionPaymentResource::class;

    public function __construct(PaymentService $service)
    {
        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return PaymentRequest::class;
    }

    /**
     * The human readable label of the resource.
     */
    protected function resourceLabel(): string
    {
        return 'Payment';
    }
}