<?php

use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentsController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\PostsController;
use App\Http\Controllers\Api\ReportsController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::patch('me', [AuthController::class, 'update']);
        Route::delete('me', [AuthController::class, 'destroy']);
        Route::patch('password', [AuthController::class, 'password']);
    });
});

// Public routes — guests can browse, logged-in users get liked_by_me
Route::get('feed', [FeedController::class, 'index']);
Route::get('posts/{post:uuid}', [PostsController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index']);

    Route::get('posts/mine', [PostsController::class, 'mine']);
    Route::post('posts', [PostsController::class, 'store']);
    Route::post('posts/{post:uuid}', [PostsController::class, 'update']);
    Route::delete('posts/{post:uuid}', [PostsController::class, 'destroy']);
    Route::post('posts/{post:uuid}/like', [PostsController::class, 'like']);

    Route::post('posts/{post:uuid}/comments', [CommentsController::class, 'store']);
    Route::delete('comments/{comment:uuid}', [CommentsController::class, 'destroy']);
    Route::post('comments/{comment:uuid}/like', [CommentsController::class, 'like']);

    Route::get('reports/mine', [ReportsController::class, 'mine']);
    Route::post('reports', [ReportsController::class, 'store']);

    Route::middleware('role:moderator,admin,super_admin')
        ->prefix('admin')
        ->group(function () {
            Route::get('dashboard', [Admin\DashboardController::class, 'index']);

            Route::get('users', [Admin\UsersController::class, 'index']);
            Route::post('users', [Admin\UsersController::class, 'store']);
            Route::get('users/{user:uuid}', [Admin\UsersController::class, 'show']);
            Route::patch('users/{user:uuid}', [Admin\UsersController::class, 'update']);
            Route::delete('users/{user:uuid}', [Admin\UsersController::class, 'destroy']);
            Route::post('users/{user:uuid}/ban', [Admin\UsersController::class, 'ban']);
            Route::post('users/{user:uuid}/unban', [Admin\UsersController::class, 'unban']);
            Route::post('users/{user:uuid}/role', [Admin\UsersController::class, 'assignRole']);

            Route::get('posts', [Admin\PostsController::class, 'index']);
            Route::post('posts', [Admin\PostsController::class, 'store']);
            Route::get('posts/{post:uuid}', [Admin\PostsController::class, 'show']);
            Route::patch('posts/{post:uuid}', [Admin\PostsController::class, 'update']);
            Route::delete('posts/{post:uuid}', [Admin\PostsController::class, 'destroy']);
            Route::patch('posts/{post:uuid}/flag', [Admin\PostsController::class, 'flag']);

            Route::get('comments', [Admin\CommentsController::class, 'index']);
            Route::post('comments', [Admin\CommentsController::class, 'store']);
            Route::patch('comments/{comment:uuid}', [Admin\CommentsController::class, 'update']);
            Route::delete('comments/{comment:uuid}', [Admin\CommentsController::class, 'destroy']);

            Route::get('reports', [Admin\ReportsController::class, 'index']);
            Route::get('reports/{report:uuid}', [Admin\ReportsController::class, 'show']);
            Route::post('reports/{report:uuid}/review', [Admin\ReportsController::class, 'review']);
            Route::post('reports/{report:uuid}/resolve', [Admin\ReportsController::class, 'resolve']);
            Route::post('reports/{report:uuid}/dismiss', [Admin\ReportsController::class, 'dismiss']);

            Route::get('bans', [Admin\BansController::class, 'index']);
            Route::post('bans', [Admin\BansController::class, 'store']);
            Route::delete('bans/{ban:uuid}', [Admin\BansController::class, 'destroy']);

            Route::get('likes', [Admin\LikesController::class, 'index']);
            Route::delete('likes/{like:uuid}', [Admin\LikesController::class, 'destroy']);

            Route::get('pages', [Admin\PagesController::class, 'index']);
            Route::post('pages', [Admin\PagesController::class, 'store']);
            Route::patch('pages/{page:uuid}', [Admin\PagesController::class, 'update']);
            Route::delete('pages/{page:uuid}', [Admin\PagesController::class, 'destroy']);

            Route::get('settings', [Admin\SettingsController::class, 'index']);
            Route::patch('settings', [Admin\SettingsController::class, 'update']);

            Route::get('activity-logs', [Admin\ActivityLogsController::class, 'index']);
        });
});
