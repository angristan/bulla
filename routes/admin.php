<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CommentsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SetupController;
use App\Http\Middleware\AdminAuthenticated;
use App\Http\Middleware\RedirectIfSetupRequired;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Setup (no auth required, but redirects if already setup)
Route::middleware([RedirectIfSetupRequired::class])->group(function (): void {
    Route::get('/setup', [SetupController::class, 'show'])->name('admin.setup');
    Route::post('/setup/info', [SetupController::class, 'storeInfo'])->name('admin.setup.info');
    Route::post('/setup/passkey/options', [SetupController::class, 'passkeyOptions'])->name('admin.setup.passkey.options');
    Route::post('/setup', [SetupController::class, 'store'])->name('admin.setup.store');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/login/passkey/options', [AuthController::class, 'passkeyOptions'])->name('admin.login.passkey.options');
    Route::post('/login/passkey/verify', [AuthController::class, 'passkeyVerify'])->name('admin.login.passkey.verify');
    Route::get('/login/2fa', [AuthController::class, 'showTwoFactorChallenge'])->name('admin.login.2fa');
    Route::post('/login/2fa', [AuthController::class, 'verifyTwoFactor'])->name('admin.login.2fa.verify');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');

// Protected admin routes
Route::middleware([RedirectIfSetupRequired::class, AdminAuthenticated::class])->group(function (): void {
    Route::get('/', DashboardController::class)->name('admin.dashboard');

    // Comments
    Route::get('/comments', [CommentsController::class, 'index'])->name('admin.comments.index');
    Route::get('/comments/{comment}', [CommentsController::class, 'show'])->name('admin.comments.show');
    Route::post('/comments/{comment}/approve', [CommentsController::class, 'approve'])->name('admin.comments.approve');
    Route::post('/comments/{comment}/spam', [CommentsController::class, 'spam'])->name('admin.comments.spam');
    Route::delete('/comments/{comment}', [CommentsController::class, 'destroy'])->name('admin.comments.destroy');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('admin.settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('admin.settings.update');
    Route::delete('/settings/wipe', [SettingsController::class, 'wipe'])->name('admin.settings.wipe');
    Route::get('/settings/claim-admin/preview', [SettingsController::class, 'previewClaimAdmin'])->name('admin.settings.claim-admin.preview');
    Route::post('/settings/claim-admin', [SettingsController::class, 'claimAdmin'])->name('admin.settings.claim-admin');

    // Telegram
    Route::post('/settings/telegram/setup-webhook', [SettingsController::class, 'setupTelegramWebhook'])->name('admin.settings.telegram.setup-webhook');
    Route::post('/settings/telegram/remove-webhook', [SettingsController::class, 'removeTelegramWebhook'])->name('admin.settings.telegram.remove-webhook');
    Route::post('/settings/telegram/test', [SettingsController::class, 'testTelegram'])->name('admin.settings.telegram.test');

    // Email
    Route::post('/settings/email/test', [SettingsController::class, 'testEmail'])->name('admin.settings.email.test');

    // Two-Factor Authentication
    Route::post('/settings/2fa/setup', [SettingsController::class, 'setupTwoFactor'])->name('admin.settings.2fa.setup');
    Route::post('/settings/2fa/enable', [SettingsController::class, 'enableTwoFactor'])->name('admin.settings.2fa.enable');
    Route::post('/settings/2fa/disable', [SettingsController::class, 'disableTwoFactor'])->name('admin.settings.2fa.disable');
    Route::post('/settings/2fa/recovery-codes', [SettingsController::class, 'regenerateRecoveryCodes'])->name('admin.settings.2fa.recovery-codes');

    // Passkeys
    Route::post('/settings/passkeys/options', [SettingsController::class, 'passkeyOptions'])->name('admin.settings.passkeys.options');
    Route::post('/settings/passkeys', [SettingsController::class, 'registerPasskey'])->name('admin.settings.passkeys.register');
    Route::delete('/settings/passkeys/{passkey}', [SettingsController::class, 'deletePasskey'])->name('admin.settings.passkeys.delete');
    Route::patch('/settings/passkeys/{passkey}', [SettingsController::class, 'renamePasskey'])->name('admin.settings.passkeys.rename');

    // Import/Export
    Route::get('/import', [ImportController::class, 'index'])->name('admin.import.index');
    Route::post('/import/isso', [ImportController::class, 'importIsso'])->name('admin.import.isso');
    Route::post('/import/json', [ImportController::class, 'importJson'])->name('admin.import.json');
    Route::post('/import/wordpress', [ImportController::class, 'importWordPress'])->name('admin.import.wordpress');
    Route::post('/import/disqus', [ImportController::class, 'importDisqus'])->name('admin.import.disqus');
    Route::get('/export', [ImportController::class, 'export'])->name('admin.export');

    // Preview
    Route::get('/preview', function () {
        $threads = \App\Models\Thread::withCount('comments')
            ->whereHas('comments')
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get()
            ->map(fn ($t) => [
                // Normalize URI to match API behavior (remove trailing slashes)
                'uri' => '/'.trim($t->uri, '/'),
                'title' => $t->title,
                'comments_count' => $t->comments_count,
            ]);

        return Inertia::render('Preview', [
            'appUrl' => config('app.url'),
            'threads' => $threads,
        ]);
    })->name('admin.preview');
});
