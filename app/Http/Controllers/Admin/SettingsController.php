<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'branding' => Setting::branding(),
        ]);
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        Setting::put('app_name', $data['app_name'], 'branding');

        $currentPath = Setting::get('app_logo');

        if ($request->hasFile('logo')) {
            if ($currentPath) {
                Storage::disk('public')->delete($currentPath);
            }
            $path = $request->file('logo')->store('branding', 'public');
            Setting::put('app_logo', $path, 'branding');
        } elseif (! empty($data['remove_logo']) && $currentPath) {
            Storage::disk('public')->delete($currentPath);
            Setting::put('app_logo', null, 'branding');
        }

        ActivityLog::record('setting.branding', null, null, [
            'app_name' => $data['app_name'],
        ]);

        return back()->with('status', 'Branding updated.');
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
