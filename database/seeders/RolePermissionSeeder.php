<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Defines the granular permission matrix and assigns it to each role.
 *
 * super_admin is intentionally NOT listed here — it bypasses every check via
 * the Gate::before hook in AuthServiceProvider (full access). admin and
 * moderator get explicit permission sets so access can be tuned per-resource
 * without touching route/middleware code.
 *
 * Idempotent: safe to run repeatedly (findOrCreate + syncPermissions).
 */
class RolePermissionSeeder extends Seeder
{
    /** All permissions in the system, grouped by resource. */
    public const PERMISSIONS = [
        'admin.access',      // enter the admin area (dashboard, activity logs)
        'users.manage',
        'reports.moderate',
        'bans.manage',
        'posts.manage',
        'comments.manage',
        'likes.manage',
        'pages.manage',
        'settings.manage',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // Moderators can do everything except manage settings.
        $moderator = array_values(array_diff(self::PERMISSIONS, ['settings.manage']));

        // Admins (and super_admins) get the full set. super_admin also bypasses
        // via Gate::before, but we grant it explicitly for consistency.
        $all = self::PERMISSIONS;

        $this->sync('moderator', $moderator);
        $this->sync('admin', $all);
        $this->sync('super_admin', $all);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function sync(string $role, array $permissions): void
    {
        Role::findOrCreate($role, 'web')->syncPermissions($permissions);
    }
}
