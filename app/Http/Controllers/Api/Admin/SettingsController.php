<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(): JsonResponse
    {
        $grouped = Setting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy('group');

        return response()->json($grouped);
    }

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
