<?php

namespace App\Actions\Settings;

use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateBranding
{
    public static function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }

    public function handle(array $data, ?UploadedFile $logo = null, bool $removeLogo = false): void
    {
        Setting::put('app_name', $data['app_name'], 'branding');

        $currentPath = Setting::get('app_logo');

        if ($logo) {
            if ($currentPath) {
                Storage::disk('public')->delete($currentPath);
            }
            Setting::put('app_logo', $logo->store('branding', 'public'), 'branding');
        } elseif ($removeLogo && $currentPath) {
            Storage::disk('public')->delete($currentPath);
            Setting::put('app_logo', null, 'branding');
        }

        ActivityLog::record('setting.branding', null, null, [
            'app_name' => $data['app_name'],
        ]);
    }
}
