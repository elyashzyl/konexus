<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RoleEnum;
use App\Models\SchoolProfile;
use App\Models\SystemSetting;
use App\Support\SystemSettingCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The grouped System Settings API.
 *
 * Part 8 – System Settings. Settings are returned grouped by their catalog
 * group so the UI can render tabbed configuration panels, and bulk updates are
 * validated against the catalog so unknown keys are rejected.
 *
 * Settings are per-school. School administrators always operate on their own
 * school; super administrators can target any school by passing a
 * `school_profile_id`.
 */
class SystemSettingsGroupController extends ApiController
{
    /**
     * The settings grouped by the catalog, seeded and unseeded, for a school.
     */
    public function index(Request $request): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request, $request->integer('school_profile_id') ?: null);

        if ($schoolId === null) {
            return $this->success(['school' => null, 'groups' => []], 'System settings retrieved.');
        }

        $rows = SystemSetting::query()
            ->where('school_profile_id', $schoolId)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('key');

        $groups = [];
        foreach (SystemSettingCatalog::GROUPS as $group => $definition) {
            $settings = [];
            foreach ($definition['settings'] as $key => $meta) {
                $row = $rows->get($key);
                $settings[] = [
                    'key' => $key,
                    'label' => $meta['label'],
                    'description' => $meta['description'] ?? '',
                    'type' => $meta['type'],
                    'options' => $meta['options'] ?? [],
                    'value' => $row?->value ?? null,
                    'is_public' => (bool) ($row?->is_public ?? false),
                ];
            }

            $groups[] = [
                'group' => $group,
                'label' => $definition['label'],
                'description' => $definition['description'] ?? '',
                'settings' => $settings,
            ];
        }

        $school = SchoolProfile::query()->find($schoolId);

        return $this->success([
            'school' => $school ? [
                'id' => $school->id,
                'name' => $school->name,
                'short_name' => $school->short_name,
            ] : null,
            'groups' => $groups,
        ], 'System settings retrieved.');
    }

    /**
     * Apply a set of setting values for the resolved school.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'school_profile_id' => ['nullable', 'integer', 'exists:school_profiles,id'],
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $schoolId = $this->resolveSchoolId($request, $validated['school_profile_id'] ?? null);

        if ($schoolId === null) {
            return $this->error('Your account is not linked to a school.', null, 422);
        }

        $updates = [];

        foreach ($validated['settings'] as $key => $value) {
            if (! SystemSettingCatalog::has($key)) {
                return $this->error("Unknown setting key [{$key}].", null, 422);
            }

            $row = SystemSetting::query()
                ->where('school_profile_id', $schoolId)
                ->where('key', $key)
                ->firstOrNew(['school_profile_id' => $schoolId, 'key' => $key]);

            $row->group = SystemSettingCatalog::groupOf($key);
            $row->value = (string) $value;
            $row->type ??= 'string';
            $row->is_public ??= $row->group === 'general';
            $row->save();

            $updates[$key] = $row->value;
        }

        return $this->success(['school_profile_id' => $schoolId, 'updated' => $updates], 'System settings updated.');
    }

    /**
     * The school the current request may manage settings for.
     *
     * School administrators are locked to their own school. Super
     * administrators may target any school, falling back to the first active
     * school when none is requested.
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
