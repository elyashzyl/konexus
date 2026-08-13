<?php

namespace App\Http\Controllers\Api\V1;

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
 */
class SystemSettingsGroupController extends ApiController
{
    /**
     * The settings grouped by the catalog, seeded and unseeded.
     */
    public function index(Request $request): JsonResponse
    {
        $rows = SystemSetting::query()->orderBy('sort_order')->get()->keyBy('key');

        $groups = [];
        foreach (SystemSettingCatalog::GROUPS as $group => $definition) {
            $settings = [];
            foreach ($definition['settings'] as $key => $meta) {
                $row = $rows->get($key);
                $settings[] = [
                    'key' => $key,
                    'label' => $meta['label'],
                    'type' => $meta['type'],
                    'options' => $meta['options'] ?? [],
                    'value' => $row?->value ?? null,
                    'is_public' => (bool) ($row?->is_public ?? false),
                ];
            }

            $groups[] = [
                'group' => $group,
                'label' => $definition['label'],
                'settings' => $settings,
            ];
        }

        return $this->success(['groups' => $groups], 'System settings retrieved.');
    }

    /**
     * Apply a set of setting values, keyed by their setting key.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $updates = [];

        foreach ($validated['settings'] as $key => $value) {
            if (! SystemSettingCatalog::has($key)) {
                return $this->error("Unknown setting key [{$key}].", null, 422);
            }

            $row = SystemSetting::query()->firstOrNew(['key' => $key]);
            $row->group = SystemSettingCatalog::groupOf($key);
            $row->value = (string) $value;
            $row->type ??= 'string';
            $row->is_public ??= $row->group === 'general';
            $row->save();

            $updates[$key] = $row->value;
        }

        return $this->success(['updated' => $updates], 'System settings updated.');
    }
}