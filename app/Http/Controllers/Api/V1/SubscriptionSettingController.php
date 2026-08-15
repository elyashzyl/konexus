<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\SubscriptionSettingRequest;
use App\Http\Resources\SubscriptionSettingResource;
use App\Models\SubscriptionSetting;
use App\Services\SubscriptionSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionSettingController extends CrudController
{
    protected string $modelClass = SubscriptionSetting::class;

    protected string $resourceClass = SubscriptionSettingResource::class;

    public function __construct(SubscriptionSettingsService $service)
    {
        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return SubscriptionSettingRequest::class;
    }

    /**
     * The human readable label of the resource.
     */
    protected function resourceLabel(): string
    {
        return 'Subscription setting';
    }

    /**
     * The settings grouped by group.
     */
    public function grouped(): JsonResponse
    {
        return $this->success($this->service->grouped(), 'Subscription settings retrieved.');
    }

    /**
     * Bulk upsert a set of settings (key => value).
     */
    public function bulk(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable'],
        ]);

        $this->service->bulkSet($request->input('settings'));

        return $this->success($this->service->grouped(), 'Subscription settings updated.');
    }
}