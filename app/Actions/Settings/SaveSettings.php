<?php

namespace App\Actions\Settings;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;

class SaveSettings
{
    public static function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string', 'max:255'],
            'settings.*.value' => ['nullable'],
            'settings.*.group' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Settings are sensitive, so the admin check runs on every surface. The
     * JSON API enforced this while the Inertia panel relied on route
     * middleware alone; keeping it here makes the rule uniform.
     */
    public static function assertAllowed(User $actor): void
    {
        if (! $actor->isAdmin()) {
            abort(403, 'Only admins can update settings.');
        }
    }

    /** Settings grouped by their `group` column, for the settings screen. */
    public static function grouped(): Collection
    {
        return Setting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy('group');
    }

    /** @return array<int,Setting> */
    public function handle(array $rows): array
    {
        $updated = [];

        foreach ($rows as $row) {
            $before = Setting::where('key', $row['key'])->first()?->only(['value']);
            $setting = Setting::put($row['key'], $row['value'] ?? null, $row['group'] ?? 'general');

            ActivityLog::record('setting.update', $setting, $before, ['value' => $row['value'] ?? null]);

            $updated[] = $setting;
        }

        return $updated;
    }
}
