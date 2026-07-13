<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/**
 * Re-adds a flat `users.role` column (removed by migrate_user_roles) as a
 * denormalized mirror of the user's spatie role, so the column can be read and
 * queried directly. Spatie remains the source of truth for permissions; the
 * column is backfilled here and kept in sync by the User model / seeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['user', 'moderator', 'admin', 'super_admin'])
                    ->default('user')
                    ->after('password')
                    ->index();
            });
        }

        // Backfill the column from the existing spatie role assignments.
        $names = Role::pluck('name', 'id');
        $morph = (new User)->getMorphClass();

        foreach (DB::table('model_has_roles')->where('model_type', $morph)->get() as $row) {
            if (isset($names[$row->role_id])) {
                DB::table('users')->where('id', $row->model_id)->update(['role' => $names[$row->role_id]]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
        }
    }
};
