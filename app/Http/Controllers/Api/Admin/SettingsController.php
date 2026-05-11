<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Admin: Settings
 *
 * Requires `admin` or `super_admin` role.
 */
class SettingsController extends Controller
{
    /**
     * List settings
     *
     * Returns all settings grouped by their `group` key.
     *
     * @response 200 {
     *   "general": [{ "id": 1, "key": "site_name", "value": "Bob", "group": "general" }]
     * }
     */
    public function index(): JsonResponse
    {
        $grouped = Setting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy('group');

        return response()->json($grouped);
    }

    /**
     * Update settings
     *
     * Upserts one or more settings. Requires `admin+`.
     *
     * @bodyParam settings array required Array of setting objects.
     * @bodyParam settings[].key string required Setting key. Example: site_name
     * @bodyParam settings[].value mixed New value. Example: Bob
     * @bodyParam settings[].group string Group name (default: `general`). Example: general
     *
     * @response 200 [{ "key": "site_name", "value": "Bob", "group": "general" }]
     * @response 403 { "message": "Only admins can update settings." }
     */
    public function update(Request $request): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403, 'Only admins can update settings.');
        }

        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string', 'max:255'],
            'settings.*.value' => ['nullable'],
            'settings.*.group' => ['nullable', 'string', 'max:255'],
        ]);

        $updated = [];
        foreach ($data['settings'] as $row) {
            $before = Setting::where('key', $row['key'])->first()?->only(['value']);
            $setting = Setting::put($row['key'], $row['value'] ?? null, $row['group'] ?? 'general');
            ActivityLog::record('setting.update', $setting, $before, ['value' => $row['value'] ?? null]);
            $updated[] = $setting;
        }

        return response()->json($updated);
    }
}
