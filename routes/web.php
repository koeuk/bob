<?php

use App\Http\Controllers\CommentsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\PostsController;
use App\Http\Controllers\ReportsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('feed', [FeedController::class, 'index'])->name('feed');

    Route::get('posts/mine', [PostsController::class, 'mine'])->name('posts.mine');
    Route::post('posts', [PostsController::class, 'store'])->name('posts.store');
    Route::get('posts/{post:uuid}', [PostsController::class, 'show'])->name('posts.show');
    Route::delete('posts/{post:uuid}', [PostsController::class, 'destroy'])->name('posts.destroy');
    Route::post('posts/{post:uuid}/like', [PostsController::class, 'like'])->name('posts.like');

    Route::post('posts/{post:uuid}/comments', [CommentsController::class, 'store'])->name('comments.store');
    Route::delete('comments/{comment:uuid}', [CommentsController::class, 'destroy'])->name('comments.destroy');
    Route::post('comments/{comment:uuid}/like', [CommentsController::class, 'like'])->name('comments.like');

    Route::get('reports/mine', [ReportsController::class, 'mine'])->name('reports.mine');
    Route::post('reports', [ReportsController::class, 'store'])->name('reports.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
