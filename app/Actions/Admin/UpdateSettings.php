<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\Setting;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateSettings
{
    use AsAction;

    /**
     * Update site settings.
     *
     * @param  array<string, mixed>  $settings
     */
    public function handle(array $settings): void
    {
        $allowedSettings = [
            'site_name',
            'site_url',
            'admin_display_name',
            'admin_email',
            'moderation_mode',
            'require_author',
            'require_email',
            'max_depth',
            'edit_window_minutes',
            'rate_limit_per_minute',
            'spam_min_time_seconds',
            'blocked_words',
            'blocked_ips',
            'allowed_origins',
            'custom_css',
            'enable_upvotes',
            'enable_downvotes',
            'enable_github_login',
            'github_client_id',
            'smtp_host',
            'smtp_port',
            'smtp_username',
            'smtp_from_address',
            'smtp_from_name',
        ];

        $encryptedSettings = [
            'smtp_password',
            'github_client_secret',
        ];

        foreach ($settings as $key => $value) {
            if (in_array($key, $allowedSettings, true)) {
                Setting::setValue($key, $value === '' ? null : (string) $value);
            }

            if (in_array($key, $encryptedSettings, true) && $value !== null && $value !== '') {
                Setting::setValue($key, (string) $value, true);
            }
        }
    }

    /**
     * Get all settings for display.
     *
     * @return array<string, mixed>
     */
    public static function getAll(): array
    {
        return [
            // General
            'site_name' => Setting::getValue('site_name', 'Marge'),
            'site_url' => Setting::getValue('site_url'),
            'admin_display_name' => Setting::getValue('admin_display_name', 'Admin'),
            'admin_email' => Setting::getValue('admin_email'),

            // Moderation
            'moderation_mode' => Setting::getValue('moderation_mode', 'none'),
            'require_author' => Setting::getValue('require_author', 'false') === 'true',
            'require_email' => Setting::getValue('require_email', 'false') === 'true',

            // Limits
            'max_depth' => (int) Setting::getValue('max_depth', '3'),
            'edit_window_minutes' => (int) Setting::getValue('edit_window_minutes', '15'),

            // Spam
            'rate_limit_per_minute' => (int) Setting::getValue('rate_limit_per_minute', '5'),
            'spam_min_time_seconds' => (int) Setting::getValue('spam_min_time_seconds', '3'),
            'blocked_words' => Setting::getValue('blocked_words', ''),
            'blocked_ips' => Setting::getValue('blocked_ips', ''),

            // CORS
            'allowed_origins' => Setting::getValue('allowed_origins', Setting::getValue('site_url', '')),

            // Appearance
            'custom_css' => Setting::getValue('custom_css', ''),

            // Voting
            'enable_upvotes' => Setting::getValue('enable_upvotes', 'true') === 'true',
            'enable_downvotes' => Setting::getValue('enable_downvotes', 'false') === 'true',

            // GitHub OAuth
            'enable_github_login' => Setting::getValue('enable_github_login', 'false') === 'true',
            'github_client_id' => Setting::getValue('github_client_id'),
            'github_configured' => Setting::getValue('github_client_id') !== null && Setting::getValue('github_client_secret') !== null,

            // Email (without password)
            'smtp_host' => Setting::getValue('smtp_host'),
            'smtp_port' => Setting::getValue('smtp_port', '587'),
            'smtp_username' => Setting::getValue('smtp_username'),
            'smtp_from_address' => Setting::getValue('smtp_from_address'),
            'smtp_from_name' => Setting::getValue('smtp_from_name'),
            'smtp_configured' => Setting::getValue('smtp_host') !== null,
        ];
    }
}
