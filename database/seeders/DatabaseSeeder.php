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
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ],
        );

        $admin = User::firstOrCreate(
            ['email' => 'mod@example.com'],
            [
                'name' => 'Mod User',
                'password' => 'password',
                'role' => 'moderator',
                'email_verified_at' => now(),
            ],
        );

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        User::firstOrCreate(
            ['email' => 'name@gmail.com'],
            [
                'name' => 'Name',
                'password' => '12345678',
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ],
        );

        if (app()->environment('local', 'testing') && User::count() < 30) {
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
