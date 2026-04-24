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

        if (app()->environment('local', 'testing') && User::count() < 20) {
            $users = User::factory(15)->create();

            Post::factory(40)
                ->recycle($users)
                ->create()
                ->each(function (Post $post) use ($users) {
                    Comment::factory(random_int(0, 5))
                        ->recycle($users)
                        ->for($post)
                        ->create();
                });

            Report::factory(8)
                ->recycle($users->push($admin, $superAdmin))
                ->create();

            Ban::factory(3)->recycle($users)->create([
                'banned_by' => $admin->id,
            ]);

            Page::factory(5)->create(['updated_by' => $superAdmin->id]);
        }
    }
}
