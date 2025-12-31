<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Auth\AuthenticateCommenterWithGitHub;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class CommenterAuthController extends Controller
{
    /**
     * Redirect to GitHub for authentication.
     */
    public function redirect(): RedirectResponse|View
    {
        // Check if GitHub login is enabled
        if (! $this->isGitHubLoginEnabled()) {
            return view('auth.github-callback', [
                'success' => false,
                'error' => 'GitHub login is not enabled.',
                'commenter' => null,
            ]);
        }

        $this->configureGitHubDriver();

        /** @var \Laravel\Socialite\Two\GithubProvider $driver */
        $driver = Socialite::driver('github');

        return $driver
            ->scopes(['read:user', 'user:email'])
            ->redirect();
    }

    /**
     * Handle callback from GitHub.
     * Renders a page that posts the result back to the opener window.
     */
    public function callback(Request $request): View
    {
        // Check if GitHub login is enabled
        if (! $this->isGitHubLoginEnabled()) {
            return view('auth.github-callback', [
                'success' => false,
                'error' => 'GitHub login is not enabled.',
                'commenter' => null,
            ]);
        }

        // Check for error from GitHub (user denied access)
        if ($request->has('error')) {
            return view('auth.github-callback', [
                'success' => false,
                'error' => $request->get('error_description', 'Authentication was denied.'),
                'commenter' => null,
            ]);
        }

        try {
            $this->configureGitHubDriver();

            /** @var \Laravel\Socialite\Two\User $githubUser */
            $githubUser = Socialite::driver('github')->user();

            AuthenticateCommenterWithGitHub::run([
                'id' => $githubUser->getId(),
                'name' => $githubUser->getName(),
                'email' => $githubUser->getEmail(),
                'avatar' => $githubUser->getAvatar(),
                'nickname' => $githubUser->getNickname(),
            ]);

            return view('auth.github-callback', [
                'success' => true,
                'error' => null,
                'commenter' => session('commenter'),
            ]);
        } catch (\Exception $e) {
            return view('auth.github-callback', [
                'success' => false,
                'error' => 'Authentication failed. Please try again.',
                'commenter' => null,
            ]);
        }
    }

    /**
     * Logout commenter (clear session).
     */
    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->session()->forget('commenter');

        return response()->json(['success' => true]);
    }

    /**
     * Configure GitHub Socialite driver with credentials from settings.
     */
    private function configureGitHubDriver(): void
    {
        config([
            'services.github.client_id' => Setting::getValue('github_client_id'),
            'services.github.client_secret' => Setting::getValue('github_client_secret'),
            'services.github.redirect' => config('app.url').'/auth/github/callback',
        ]);
    }

    /**
     * Check if GitHub login is enabled.
     */
    private function isGitHubLoginEnabled(): bool
    {
        $credentialsConfigured = Setting::getValue('github_client_id')
            && Setting::getValue('github_client_secret');

        $settingEnabled = Setting::getValue('enable_github_login', 'false') === 'true';

        return $credentialsConfigured && $settingEnabled;
    }
}
