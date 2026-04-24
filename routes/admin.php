<?php

use App\Http\Controllers\Admin\ActivityLogsController;
use App\Http\Controllers\Admin\BansController;
use App\Http\Controllers\Admin\CommentsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PagesController;
use App\Http\Controllers\Admin\PostsController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UsersController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:moderator,admin,super_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::redirect('/', '/admin/dashboard');

        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('users', [UsersController::class, 'index'])->name('users.index');
        Route::get('users/create', [UsersController::class, 'create'])->name('users.create');
        Route::post('users', [UsersController::class, 'store'])->name('users.store');
        Route::get('users/{user:uuid}', [UsersController::class, 'show'])->name('users.show');
        Route::patch('users/{user:uuid}', [UsersController::class, 'update'])->name('users.update');
        Route::delete('users/{user:uuid}', [UsersController::class, 'destroy'])->name('users.destroy');
        Route::post('users/{user:uuid}/ban', [UsersController::class, 'ban'])->name('users.ban');
        Route::post('users/{user:uuid}/unban', [UsersController::class, 'unban'])->name('users.unban');
        Route::post('users/{user:uuid}/role', [UsersController::class, 'assignRole'])->name('users.role');

        Route::get('reports', [ReportsController::class, 'index'])->name('reports.index');
        Route::get('reports/{report:uuid}', [ReportsController::class, 'show'])->name('reports.show');
        Route::post('reports/{report:uuid}/review', [ReportsController::class, 'review'])->name('reports.review');
        Route::post('reports/{report:uuid}/resolve', [ReportsController::class, 'resolve'])->name('reports.resolve');
        Route::post('reports/{report:uuid}/dismiss', [ReportsController::class, 'dismiss'])->name('reports.dismiss');

        Route::get('bans', [BansController::class, 'index'])->name('bans.index');
        Route::post('bans', [BansController::class, 'store'])->name('bans.store');
        Route::delete('bans/{ban:uuid}', [BansController::class, 'destroy'])->name('bans.destroy');

        Route::get('posts', [PostsController::class, 'index'])->name('posts.index');
        Route::get('posts/create', [PostsController::class, 'create'])->name('posts.create');
        Route::post('posts', [PostsController::class, 'store'])->name('posts.store');
        Route::get('posts/{post:uuid}', [PostsController::class, 'show'])->name('posts.show');
        Route::get('posts/{post:uuid}/edit', [PostsController::class, 'edit'])->name('posts.edit');
        Route::patch('posts/{post:uuid}', [PostsController::class, 'update'])->name('posts.update');
        Route::delete('posts/{post:uuid}', [PostsController::class, 'destroy'])->name('posts.destroy');
        Route::patch('posts/{post:uuid}/flag', [PostsController::class, 'flag'])->name('posts.flag');

        Route::get('comments', [CommentsController::class, 'index'])->name('comments.index');
        Route::post('comments', [CommentsController::class, 'store'])->name('comments.store');
        Route::patch('comments/{comment:uuid}', [CommentsController::class, 'update'])->name('comments.update');
        Route::delete('comments/{comment:uuid}', [CommentsController::class, 'destroy'])->name('comments.destroy');

        Route::get('pages', [PagesController::class, 'index'])->name('pages.index');
        Route::get('pages/create', [PagesController::class, 'create'])->name('pages.create');
        Route::post('pages', [PagesController::class, 'store'])->name('pages.store');
        Route::get('pages/{page:uuid}/edit', [PagesController::class, 'edit'])->name('pages.edit');
        Route::patch('pages/{page:uuid}', [PagesController::class, 'update'])->name('pages.update');
        Route::delete('pages/{page:uuid}', [PagesController::class, 'destroy'])->name('pages.destroy');

        Route::middleware('role:super_admin,admin')->group(function () {
            Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::patch('settings', [SettingsController::class, 'update'])->name('settings.update');
        });

        Route::get('activity-logs', [ActivityLogsController::class, 'index'])->name('activity-logs.index');
    });
