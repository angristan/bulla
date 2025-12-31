<?php

declare(strict_types=1);

use App\Http\Controllers\CommenterAuthController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\FeedController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

// GitHub OAuth for commenters
Route::get('/auth/github/redirect', [CommenterAuthController::class, 'redirect'])
    ->name('auth.github.redirect');
Route::get('/auth/github/callback', [CommenterAuthController::class, 'callback'])
    ->name('auth.github.callback');
Route::post('/auth/logout', [CommenterAuthController::class, 'logout'])
    ->name('auth.logout');

// Email unsubscribe (public)
Route::get('/unsubscribe/{token}', [EmailController::class, 'unsubscribe'])->name('unsubscribe');

// Email moderation links (public but require valid token)
Route::get('/moderate/{comment}/approve/{token}', [EmailController::class, 'approve'])->name('moderate.approve');
Route::get('/moderate/{comment}/delete/{token}', [EmailController::class, 'delete'])->name('moderate.delete');

// Atom feeds
Route::get('/feed/recent.atom', [FeedController::class, 'recent'])->name('feed.recent');
Route::get('/feed/{uri}.atom', [FeedController::class, 'thread'])->where('uri', '.*')->name('feed.thread');
