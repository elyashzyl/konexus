<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RoleEnum;
use App\Http\Requests\Api\SubscriptionSettingRequest;
use App\Http\Resources\SubscriptionSettingResource;
use App\Models\SchoolProfile;
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
     * The settings grouped by group for the resolved school.
     */
    public function grouped(Request $request): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request, $request->integer('school_profile_id') ?: null);

        return $this->success($this->service->grouped($schoolId), 'Subscription settings retrieved.');
    }

    /**
     * Bulk upsert a set of settings (key => value) for the resolved school.
     */
    public function bulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'school_profile_id' => ['nullable', 'integer', 'exists:school_profiles,id'],
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable'],
        ]);

        $schoolId = $this->resolveSchoolId($request, $validated['school_profile_id'] ?? null);

        $this->service->bulkSet($validated['settings'], $schoolId);

        return $this->success($this->service->grouped($schoolId), 'Subscription settings updated.');
    }

    /**
     * The school the current request may manage settings for.
     *
     * School administrators are locked to their own school. Platform operators
     * may target any school, falling back to the first active school when none
     * is requested.
     */
    private function resolveSchoolId(Request $request, ?int $requested = null): ?int
    {
        $user = $request->user();

        if ($user->hasRole(RoleEnum::SCHOOL_ADMINISTRATOR->roleName())) {
            return $user->school_profile_id !== null ? (int) $user->school_profile_id : null;
        }

        if ($requested !== null) {
            return $requested;
        }

        $fallback = SchoolProfile::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        return $fallback !== null ? (int) $fallback : null;
    }
}
