<?php

use App\Http\Controllers\Admin\ActivityLogsController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BansController;
use App\Http\Controllers\Admin\CommentsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PagesController;
use App\Http\Controllers\Admin\PostsController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UsersController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'role:moderator,admin,super_admin'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/user', [AuthController::class, 'user']);

        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/dashboard/charts', [DashboardController::class, 'charts']);
        Route::get('/dashboard/recent-activity', [DashboardController::class, 'recentActivity']);

        Route::get('/reports', [ReportsController::class, 'index']);
        Route::get('/reports/{report}', [ReportsController::class, 'show']);
        Route::put('/reports/{report}/review', [ReportsController::class, 'review']);
        Route::put('/reports/{report}/resolve', [ReportsController::class, 'resolve']);
        Route::put('/reports/{report}/dismiss', [ReportsController::class, 'dismiss']);

        Route::get('/bans', [BansController::class, 'index']);
        Route::get('/bans/active', [BansController::class, 'active']);
        Route::post('/bans', [BansController::class, 'store']);
        Route::delete('/bans/{ban}', [BansController::class, 'destroy']);

        Route::get('/posts', [PostsController::class, 'index']);
        Route::get('/posts/{post}', [PostsController::class, 'show']);
        Route::delete('/posts/{post}', [PostsController::class, 'destroy']);
        Route::put('/posts/{post}/flag', [PostsController::class, 'flag']);

        Route::get('/comments', [CommentsController::class, 'index']);
        Route::delete('/comments/{comment}', [CommentsController::class, 'destroy']);
    });

    Route::middleware(['auth:sanctum', 'role:admin,super_admin'])->group(function () {
        Route::get('/users', [UsersController::class, 'index']);
        Route::get('/users/{user}', [UsersController::class, 'show']);
        Route::put('/users/{user}', [UsersController::class, 'update']);
        Route::delete('/users/{user}', [UsersController::class, 'destroy']);
        Route::post('/users/{user}/ban', [UsersController::class, 'ban']);
        Route::delete('/users/{user}/ban', [UsersController::class, 'unban']);
        Route::get('/users/{user}/activity', [UsersController::class, 'activity']);

        Route::get('/pages', [PagesController::class, 'index']);
        Route::post('/pages', [PagesController::class, 'store']);
        Route::get('/pages/{page}', [PagesController::class, 'show']);
        Route::put('/pages/{page}', [PagesController::class, 'update']);
        Route::delete('/pages/{page}', [PagesController::class, 'destroy']);

        Route::get('/activity-logs', [ActivityLogsController::class, 'index']);
    });

    Route::middleware(['auth:sanctum', 'role:super_admin'])->group(function () {
        Route::put('/users/{user}/role', [UsersController::class, 'assignRole']);
        Route::get('/settings', [SettingsController::class, 'index']);
        Route::put('/settings', [SettingsController::class, 'update']);
    });
});
