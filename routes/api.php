<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Public API routes for embed widget
Route::prefix('threads/{uri}')->group(function (): void {
    // Route::get('comments', GetThreadComments::class);
    // Route::post('comments', CreateComment::class);
});

// Route::get('comments/{comment}', GetComment::class);
// Route::put('comments/{comment}', UpdateComment::class);
// Route::delete('comments/{comment}', DeleteComment::class);
// Route::post('comments/{comment}/upvote', UpvoteComment::class);

// Route::post('comments/preview', PreviewMarkdown::class);
// Route::get('config', GetClientConfig::class);
// Route::post('counts', GetCommentCounts::class);
