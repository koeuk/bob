<?php

namespace Database\Seeders;

use App\Models\Ban;
use App\Models\Comment;
use App\Models\Page;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Creates the four roles, the permissions, and assigns them.
        $this->call(RolePermissionSeeder::class);

        // Primary super-admin — created in every environment. Credentials come
        // from env vars so production can use a strong, secret password; the
        // defaults keep local development frictionless.
        // Refuse to seed a weak default password anywhere except local/testing.
        // Allow-listing (rather than blocking only the literal "production")
        // means staging/demo/prod-like environments are covered too — they
        // would otherwise get a super-admin of admin@gmail.com / 12345678.
        if (! app()->environment('local', 'testing') && ! env('ADMIN_PASSWORD')) {
            throw new \RuntimeException(
                'Refusing to seed the super-admin with the default password in the "'
                .app()->environment().'" environment. '
                .'Set the ADMIN_PASSWORD (and ADMIN_EMAIL) environment variables first.'
            );
        }

        $superAdmin = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@gmail.com')],
            [
                'name' => env('ADMIN_NAME', 'Super Admin'),
                'password' => env('ADMIN_PASSWORD', '12345678'),
            ],
        );
        $superAdmin->syncRoles(['super_admin']);
        // markEmailAsVerified() force-fills, so it works despite
        // email_verified_at deliberately not being mass-assignable.
        $superAdmin->markEmailAsVerified();

        // Personal admin account — local/testing only, always seeded.
        if (app()->environment('local', 'testing')) {
            $koeuk = User::firstOrCreate(
                ['email' => 'koeukkos@gmail.com'],
                [
                    'name' => 'Koeuk',
                    'password' => '12345678',
                ],
            );
            $koeuk->syncRoles(['admin']);
            $koeuk->markEmailAsVerified();
        }

        if (app()->environment('local', 'testing') && User::count() < 30) {
            // Extra fixed dev accounts — never created outside local/testing.
            $admin = User::firstOrCreate(
                ['email' => 'mod@gmail.com'],
                [
                    'name' => 'Mod User',
                    'password' => '12345678',
                ],
            );
            $admin->syncRoles(['moderator']);
            $admin->markEmailAsVerified();

            $testUser = User::firstOrCreate(
                ['email' => 'test@gmail.com'],
                [
                    'name' => 'Test User',
                    'password' => '12345678',
                ],
            );
            $testUser->syncRoles(['user']);
            $testUser->markEmailAsVerified();

            $secondSuper = User::firstOrCreate(
                ['email' => 'name@gmail.com'],
                [
                    'name' => 'Name',
                    'password' => '12345678',
                ],
            );
            $secondSuper->syncRoles(['super_admin']);
            $secondSuper->markEmailAsVerified();

            $users = User::factory(25)->create();

            $posts = Post::factory(60)
                ->recycle($users)
                ->create()
                ->each(function (Post $post) use ($users) {
                    Comment::factory(random_int(0, 5))
                        ->recycle($users)
                        ->for($post)
                        ->create();
                });

            // Ensure user 23 has at least 10 posts with comments
            $user23 = User::find(23);
            if ($user23) {
                $user23Posts = Post::factory(10)->create(['user_id' => $user23->id]);
                $user23Posts->each(function (Post $post) use ($users) {
                    Comment::factory(random_int(3, 5))->recycle($users)->for($post)->create();
                });
                Comment::factory(10)->recycle($user23Posts)->create(['user_id' => $user23->id]);
                Report::factory(10)->recycle($user23Posts)->create(['reporter_id' => $user23->id]);
                Ban::factory(3)->create(['user_id' => $user23->id, 'banned_by' => $admin->id]);
            }

            Report::factory(10)
                ->recycle($users->push($admin, $superAdmin))
                ->create();

            Ban::factory(5)->recycle($users)->create([
                'banned_by' => $admin->id,
            ]);

            Page::factory(5)->create(['updated_by' => $superAdmin->id]);
        }
    }
}
