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
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['user', 'moderator', 'admin', 'super_admin'] as $role) {
            \Spatie\Permission\Models\Role::findOrCreate($role, 'web');
        }

        // Primary super-admin — created in every environment. Credentials come
        // from env vars so production can use a strong, secret password; the
        // defaults keep local development frictionless.
        $superAdmin = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@gmail.com')],
            [
                'name' => env('ADMIN_NAME', 'Super Admin'),
                'password' => env('ADMIN_PASSWORD', '12345678'),
                'email_verified_at' => now(),
            ],
        );
        $superAdmin->syncRoles(['super_admin']);

        if (app()->environment('local', 'testing') && User::count() < 30) {
            // Extra fixed dev accounts — never created outside local/testing.
            $admin = User::firstOrCreate(
                ['email' => 'mod@gmail.com'],
                [
                    'name' => 'Mod User',
                    'password' => '12345678',
                    'email_verified_at' => now(),
                ],
            );
            $admin->syncRoles(['moderator']);

            User::firstOrCreate(
                ['email' => 'test@gmail.com'],
                [
                    'name' => 'Test User',
                    'password' => '12345678',
                    'email_verified_at' => now(),
                ],
            )->syncRoles(['user']);

            User::firstOrCreate(
                ['email' => 'name@gmail.com'],
                [
                    'name' => 'Name',
                    'password' => '12345678',
                    'email_verified_at' => now(),
                ],
            )->syncRoles(['super_admin']);

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
