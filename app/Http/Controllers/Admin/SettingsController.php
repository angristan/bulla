<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\UpdateSettings;
use App\Actions\Admin\WipeAllData;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Show settings page.
     */
    public function index(): Response
    {
        $settings = UpdateSettings::getAll();

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // General
            'site_name' => ['nullable', 'string', 'max:255'],
            'site_url' => ['nullable', 'url', 'max:1024'],
            'admin_display_name' => ['nullable', 'string', 'max:255'],
            'admin_email' => ['nullable', 'email', 'max:255'],

            // Moderation
            'moderation_mode' => ['nullable', 'string', 'in:none,all'],
            'require_author' => ['nullable', 'boolean'],
            'require_email' => ['nullable', 'boolean'],
            'enable_upvotes' => ['nullable', 'boolean'],
            'enable_downvotes' => ['nullable', 'boolean'],
            'enable_github_login' => ['nullable', 'boolean'],
            'github_client_id' => ['nullable', 'string', 'max:255'],
            'github_client_secret' => ['nullable', 'string', 'max:255'],

            // Limits
            'max_depth' => ['nullable', 'integer', 'min:0', 'max:3'],
            'edit_window_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],

            // Spam
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:1', 'max:100'],
            'spam_min_time_seconds' => ['nullable', 'integer', 'min:0', 'max:60'],
            'blocked_words' => ['nullable', 'string', 'max:10000'],
            'blocked_ips' => ['nullable', 'string', 'max:10000'],

            // CORS
            'allowed_origins' => ['nullable', 'string', 'max:2000'],

            // Appearance
            'custom_css' => ['nullable', 'string', 'max:50000'],

            // Email
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_from_address' => ['nullable', 'email', 'max:255'],
            'smtp_from_name' => ['nullable', 'string', 'max:255'],
        ]);

        // Convert booleans to strings
        foreach (['require_author', 'require_email', 'enable_upvotes', 'enable_downvotes', 'enable_github_login'] as $key) {
            if (isset($validated[$key])) {
                $validated[$key] = $validated[$key] ? 'true' : 'false';
            }
        }

        // Downvotes require upvotes to be enabled
        if (isset($validated['enable_upvotes']) && $validated['enable_upvotes'] === 'false') {
            $validated['enable_downvotes'] = 'false';
        }

        UpdateSettings::run($validated);

        return back()->with('success', 'Settings updated.');
    }

    /**
     * Wipe all data (comments, threads, import mappings).
     */
    public function wipe(): RedirectResponse
    {
        $counts = WipeAllData::run();

        return back()->with(
            'success',
            "Deleted {$counts['comments']} comments, {$counts['threads']} threads, and {$counts['mappings']} import mappings."
        );
    }

    /**
     * Preview comments that would be claimed as admin.
     */
    public function previewClaimAdmin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['nullable', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
        ]);

        if (empty($validated['email']) && empty($validated['author'])) {
            return response()->json(['count' => 0, 'comments' => []]);
        }

        $query = Comment::with('thread')->where('is_admin', false);

        if (! empty($validated['email'])) {
            $query->where('email', $validated['email']);
        }

        if (! empty($validated['author'])) {
            $query->where('author', $validated['author']);
        }

        $count = $query->count();
        $comments = $query->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn (Comment $c) => [
                'id' => $c->id,
                'author' => $c->author,
                'body_excerpt' => mb_substr(strip_tags($c->body_html), 0, 100),
                'thread_uri' => $c->thread->uri,
                'created_at' => $c->created_at->diffForHumans(),
            ]);

        return response()->json(['count' => $count, 'comments' => $comments]);
    }

    /**
     * Claim comments as admin.
     */
    public function claimAdmin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['nullable', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
        ]);

        if (empty($validated['email']) && empty($validated['author'])) {
            return back()->with('error', 'Please provide an email or author name.');
        }

        $query = Comment::where('is_admin', false);

        if (! empty($validated['email'])) {
            $query->where('email', $validated['email']);
        }

        if (! empty($validated['author'])) {
            $query->where('author', $validated['author']);
        }

        $count = $query->update(['is_admin' => true]);

        return back()->with('success', "Marked {$count} comments as admin.");
    }
}
