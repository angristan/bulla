<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Spam\GenerateTimestamp;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    /**
     * Get client configuration.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $isGuest = $request->query('guest') === '1';
        $isAdmin = $isGuest
            ? false
            : (bool) $request->session()->get('admin_authenticated', false);

        // Get commenter session data (GitHub authenticated)
        // Note: Guest mode hides admin status but still allows GitHub auth
        $commenter = $request->session()->get('commenter');

        // Check if GitHub auth is enabled (credentials configured AND setting enabled)
        $githubAuthEnabled = Setting::getValue('github_client_id')
            && Setting::getValue('github_client_secret')
            && Setting::getValue('enable_github_login', 'false') === 'true';

        $config = [
            'site_name' => Setting::getValue('site_name', 'Marge'),
            'require_author' => Setting::getValue('require_author', 'false') === 'true',
            'require_email' => Setting::getValue('require_email', 'false') === 'true',
            'moderation_mode' => Setting::getValue('moderation_mode', 'none'),
            'max_depth' => (int) Setting::getValue('max_depth', '3'),
            'edit_window_minutes' => (int) Setting::getValue('edit_window_minutes', '15'),
            'timestamp' => GenerateTimestamp::run(),
            'is_admin' => $isAdmin,
            'enable_upvotes' => Setting::getValue('enable_upvotes', 'true') === 'true',
            'enable_downvotes' => Setting::getValue('enable_downvotes', 'false') === 'true',
            'github_auth_enabled' => $githubAuthEnabled,
            'commenter' => $commenter,
        ];

        return response()->json($config);
    }
}
