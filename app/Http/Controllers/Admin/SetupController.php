<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Passkey\GenerateRegistrationOptions;
use App\Actions\Admin\Passkey\VerifyRegistration;
use App\Actions\Admin\SetupAdmin;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SetupController extends Controller
{
    /**
     * Show setup wizard.
     */
    public function show(): Response
    {
        return Inertia::render('Setup');
    }

    /**
     * Store site info and admin details (step 1).
     */
    public function storeInfo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'site_url' => ['required', 'url', 'max:1024'],
            'username' => ['required', 'string', 'min:3', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        // Store in session for later
        $request->session()->put('setup_info', $validated);

        return response()->json(['success' => true]);
    }

    /**
     * Generate passkey registration options (step 2).
     */
    public function passkeyOptions(Request $request): JsonResponse
    {
        $setupInfo = $request->session()->get('setup_info');

        if (! $setupInfo) {
            return response()->json(['error' => 'Setup info not found. Please start over.'], 400);
        }

        // Temporarily set the required settings for passkey generation
        Setting::setValue('site_name', $setupInfo['site_name']);
        Setting::setValue('site_url', $setupInfo['site_url']);
        Setting::setValue('admin_username', $setupInfo['username']);
        Setting::setValue('admin_email', $setupInfo['email']);

        $result = GenerateRegistrationOptions::run();

        // Store challenge in session
        $request->session()->put('setup_passkey_challenge', $result['challenge']);
        $request->session()->put('setup_passkey_challenge_at', now()->timestamp);

        return response()->json($result['options']);
    }

    /**
     * Complete setup with passkey registration (step 3).
     */
    public function store(Request $request): JsonResponse
    {
        $setupInfo = $request->session()->get('setup_info');
        $challenge = $request->session()->get('setup_passkey_challenge');
        $challengeAt = $request->session()->get('setup_passkey_challenge_at', 0);

        if (! $setupInfo) {
            return response()->json(['error' => 'Setup info not found. Please start over.'], 400);
        }

        // Expire after 2 minutes
        if (! $challenge || now()->timestamp - $challengeAt > 120) {
            $request->session()->forget(['setup_passkey_challenge', 'setup_passkey_challenge_at']);

            return response()->json(['error' => 'Challenge expired. Please try again.'], 400);
        }

        $validated = $request->validate([
            'credential' => ['required', 'array'],
            'passkey_name' => ['required', 'string', 'max:255'],
        ]);

        // Verify and register the passkey
        $result = VerifyRegistration::run(
            $validated['credential'],
            $challenge,
            $validated['passkey_name']
        );

        if (! $result['success']) {
            return response()->json(['error' => $result['message'] ?? 'Passkey registration failed'], 400);
        }

        // Complete setup (settings were already saved in passkeyOptions)
        SetupAdmin::run(
            $setupInfo['username'],
            $setupInfo['email'],
            $setupInfo['site_name'],
            $setupInfo['site_url']
        );

        // Clear session data
        $request->session()->forget([
            'setup_info',
            'setup_passkey_challenge',
            'setup_passkey_challenge_at',
        ]);

        // Auto-login after setup
        $request->session()->regenerate();
        $request->session()->put('admin_authenticated', true);

        return response()->json([
            'success' => true,
            'redirect' => route('admin.dashboard'),
        ]);
    }
}
