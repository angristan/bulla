<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Passkey\GenerateAuthenticationOptions;
use App\Actions\Admin\Passkey\VerifyAuthentication;
use App\Actions\Admin\TwoFactor\VerifyTwoFactorCode;
use App\Http\Controllers\Controller;
use App\Models\Passkey;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    /**
     * Show login form.
     */
    public function showLogin(Request $request): Response
    {
        return Inertia::render('Auth/Login', [
            'hasPasskeys' => Passkey::exists(),
        ]);
    }

    /**
     * Generate passkey authentication options.
     */
    public function passkeyOptions(Request $request): JsonResponse
    {
        $result = GenerateAuthenticationOptions::run();

        if ($result === null) {
            return response()->json(['error' => 'No passkeys registered'], 400);
        }

        // Store challenge in session
        $request->session()->put('passkey_challenge', $result['challenge']);
        $request->session()->put('passkey_challenge_at', now()->timestamp);

        return response()->json($result['options']);
    }

    /**
     * Verify passkey authentication.
     */
    public function passkeyVerify(Request $request): JsonResponse
    {
        $challenge = $request->session()->get('passkey_challenge');
        $challengeAt = $request->session()->get('passkey_challenge_at', 0);

        // Expire after 2 minutes
        if (! $challenge || now()->timestamp - $challengeAt > 120) {
            $request->session()->forget(['passkey_challenge', 'passkey_challenge_at']);

            return response()->json(['error' => 'Challenge expired'], 400);
        }

        $validated = $request->validate([
            'credential' => ['required', 'array'],
        ]);

        $result = VerifyAuthentication::run($validated['credential'], $challenge);

        if (! $result['success']) {
            return response()->json(['error' => $result['message'] ?? 'Verification failed'], 400);
        }

        // Clear challenge
        $request->session()->forget(['passkey_challenge', 'passkey_challenge_at']);

        // Check if 2FA is enabled
        if (Setting::getValue('totp_enabled', 'false') === 'true') {
            $request->session()->regenerate();
            $request->session()->put('admin_passkey_verified', true);
            $request->session()->put('admin_passkey_verified_at', now()->timestamp);

            return response()->json(['redirect' => route('admin.login.2fa')]);
        }

        // Fully authenticate
        $request->session()->regenerate();
        $request->session()->put('admin_authenticated', true);

        return response()->json(['redirect' => route('admin.dashboard')]);
    }

    /**
     * Show two-factor authentication challenge.
     */
    public function showTwoFactorChallenge(Request $request): Response|RedirectResponse
    {
        // Must have verified passkey first
        if (! $request->session()->get('admin_passkey_verified')) {
            return redirect()->route('admin.login');
        }

        // Expire after 5 minutes
        $verifiedAt = $request->session()->get('admin_passkey_verified_at', 0);
        if (now()->timestamp - $verifiedAt > 300) {
            $request->session()->forget(['admin_passkey_verified', 'admin_passkey_verified_at']);

            return redirect()->route('admin.login')->withErrors([
                'passkey' => 'Session expired. Please login again.',
            ]);
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    /**
     * Verify two-factor authentication code.
     */
    public function verifyTwoFactor(Request $request): RedirectResponse
    {
        // Must have verified passkey first
        if (! $request->session()->get('admin_passkey_verified')) {
            return redirect()->route('admin.login');
        }

        // Expire after 5 minutes
        $verifiedAt = $request->session()->get('admin_passkey_verified_at', 0);
        if (now()->timestamp - $verifiedAt > 300) {
            $request->session()->forget(['admin_passkey_verified', 'admin_passkey_verified_at']);

            return redirect()->route('admin.login')->withErrors([
                'passkey' => 'Session expired. Please login again.',
            ]);
        }

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        if (! VerifyTwoFactorCode::run($validated['code'])) {
            return back()->withErrors(['code' => 'Invalid authentication code.']);
        }

        // Clear intermediate state and fully authenticate
        $request->session()->forget(['admin_passkey_verified', 'admin_passkey_verified_at']);
        $request->session()->put('admin_authenticated', true);

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'admin_authenticated',
            'admin_passkey_verified',
            'admin_passkey_verified_at',
            'passkey_challenge',
            'passkey_challenge_at',
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
