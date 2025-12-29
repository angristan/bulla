<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Dashboard');
});

// Admin routes will be added here
// Route::prefix('admin')->middleware(['auth'])->group(function () {
//     Route::get('/', ShowDashboard::class);
//     Route::get('/comments', ShowComments::class);
//     Route::get('/settings', ShowSettings::class);
//     Route::get('/import', ShowImport::class);
// });
