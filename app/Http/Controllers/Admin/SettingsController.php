<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Settings\SaveSettings;
use App\Actions\Settings\UpdateBranding;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/settings/index', [
            'groups' => SaveSettings::grouped(),
            'branding' => Setting::branding(),
        ]);
    }

    public function updateBranding(Request $request, UpdateBranding $updateBranding): RedirectResponse
    {
        // Branding writes to the same settings table as update(), so it gets
        // the same admin check — otherwise the two writers on this controller
        // would disagree the moment settings.manage is granted more widely.
        SaveSettings::assertAllowed($request->user());

        $data = $request->validate(UpdateBranding::rules());

        $updateBranding->handle(
            $data,
            $request->file('logo'),
            (bool) ($data['remove_logo'] ?? false),
        );

        return back()->with('status', 'Branding updated.');
    }

    public function update(Request $request, SaveSettings $saveSettings): RedirectResponse
    {
        SaveSettings::assertAllowed($request->user());

        $data = $request->validate(SaveSettings::rules());

        $saveSettings->handle($data['settings']);

        return back()->with('status', 'Settings saved.');
    }
}
