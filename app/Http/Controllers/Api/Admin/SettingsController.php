<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Settings\SaveSettings;
use App\Http\Controllers\Controller;
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
        return response()->json(SaveSettings::grouped());
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
    public function update(Request $request, SaveSettings $saveSettings): JsonResponse
    {
        SaveSettings::assertAllowed($request->user());

        $data = $request->validate(SaveSettings::rules());

        return response()->json($saveSettings->handle($data['settings']));
    }
}
