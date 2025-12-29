<?php

declare(strict_types=1);

use App\Http\Controllers\EmailController;
use App\Http\Controllers\FeedController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

// Email verification and unsubscribe (public)
Route::get('/verify/{token}', [EmailController::class, 'verify'])->name('verify');
Route::get('/unsubscribe/{token}', [EmailController::class, 'unsubscribe'])->name('unsubscribe');

// Email moderation links (public but require valid token)
Route::get('/moderate/{comment}/approve/{token}', [EmailController::class, 'approve'])->name('moderate.approve');
Route::get('/moderate/{comment}/delete/{token}', [EmailController::class, 'delete'])->name('moderate.delete');

// Atom feeds
Route::get('/feed/recent.atom', [FeedController::class, 'recent'])->name('feed.recent');
Route::get('/feed/{uri}.atom', [FeedController::class, 'thread'])->where('uri', '.*')->name('feed.thread');
