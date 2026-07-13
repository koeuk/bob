<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Copies the legacy `users.role` value into the spatie role pivot for any
 * pre-existing installs. The `role` column is retained as a denormalized
 * mirror (kept in sync by AppServiceProvider), so it is no longer dropped.
 * On a fresh install this runs against an empty users table and is a no-op;
 * the seeders populate roles afterwards.
 */
return new class extends Migration
{
    private array $roles = ['user', 'moderator', 'admin', 'super_admin'];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->roles as $name) {
            Role::findOrCreate($name, 'web');
        }

        $roleIds = Role::where('guard_name', 'web')->pluck('id', 'name');
        $morph = (new User)->getMorphClass();

        foreach (DB::table('users')->select('id', 'role')->get() as $u) {
            $roleId = $roleIds[$u->role] ?? null;
            if ($roleId) {
                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id'    => $roleId,
                    'model_type' => $morph,
                    'model_id'   => $u->id,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Reverse of up(): remove the user→role pivot rows it inserted.
        DB::table('model_has_roles')->where('model_type', (new User)->getMorphClass())->delete();
    }
};
