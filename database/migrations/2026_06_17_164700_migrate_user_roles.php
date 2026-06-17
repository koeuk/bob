<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Moves roles out of the `users.role` column into the roles table:
 * creates the four roles, assigns each existing user their role,
 * then drops the column.
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

        if (Schema::hasColumn('users', 'role')) {
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

            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('role')->default('user')->after('password'));
        }

        $names = Role::pluck('name', 'id');
        $morph = (new User)->getMorphClass();

        foreach (DB::table('model_has_roles')->where('model_type', $morph)->get() as $row) {
            if (isset($names[$row->role_id])) {
                DB::table('users')->where('id', $row->model_id)->update(['role' => $names[$row->role_id]]);
            }
        }
    }
};
