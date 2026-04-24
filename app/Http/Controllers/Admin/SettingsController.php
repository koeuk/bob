<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        $grouped = Setting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy('group');

        return Inertia::render('admin/settings/index', [
            'groups' => $grouped,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string', 'max:255'],
            'settings.*.value' => ['nullable'],
            'settings.*.group' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($data['settings'] as $row) {
            $before = Setting::where('key', $row['key'])->first()?->only(['value']);
            $setting = Setting::put($row['key'], $row['value'] ?? null, $row['group'] ?? 'general');
            ActivityLog::record('setting.update', $setting, $before, ['value' => $row['value'] ?? null]);
        }

        return back()->with('status', 'Settings saved.');
    }
}
