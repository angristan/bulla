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
            'admin_badge_label',
            'admin_email',
            'moderation_mode',
            'require_author',
            'require_email',
            'max_depth',
            'edit_window_minutes',
            'rate_limit_per_minute',
            'spam_min_time_seconds',
            'blocked_words',
            'allowed_origins',
            'custom_css',
            'accent_color',
            'enable_upvotes',
            'enable_downvotes',
            'enable_github_login',
            'github_client_id',
            'hide_branding',
            // Telegram
            'telegram_chat_id',
            'enable_telegram',
            'telegram_notify_upvotes',
        ];

        $encryptedSettings = [
            'github_client_secret',
            'telegram_bot_token',
            'telegram_webhook_secret',
        ];

        // Settings that should have trailing slashes removed
        $urlSettings = ['site_url', 'allowed_origins'];

        foreach ($settings as $key => $value) {
            if (in_array($key, $allowedSettings, true)) {
                $sanitized = $value === '' ? null : (string) $value;

                // Remove trailing slashes from URL settings
                if ($sanitized !== null && in_array($key, $urlSettings, true)) {
                    $sanitized = implode(',', array_map(
                        fn ($url) => rtrim(trim($url), '/'),
                        explode(',', $sanitized)
                    ));
                }

                Setting::setValue($key, $sanitized);
            }

            if (in_array($key, $encryptedSettings, true) && $value !== null && $value !== '' && $value !== '••••••••') {
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
            'site_name' => Setting::getValue('site_name', 'Bulla'),
            'site_url' => Setting::getValue('site_url'),
            'admin_display_name' => Setting::getValue('admin_display_name', 'Admin'),
            'admin_badge_label' => Setting::getValue('admin_badge_label', 'Author'),
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

            // CORS
            'allowed_origins' => Setting::getValue('allowed_origins', Setting::getValue('site_url', '')),

            // Appearance
            'custom_css' => Setting::getValue('custom_css', ''),
            'accent_color' => Setting::getValue('accent_color', '#3b82f6'),
            'hide_branding' => Setting::getValue('hide_branding', 'false') === 'true',

            // Voting
            'enable_upvotes' => Setting::getValue('enable_upvotes', 'true') === 'true',
            'enable_downvotes' => Setting::getValue('enable_downvotes', 'false') === 'true',

            // GitHub OAuth
            'enable_github_login' => Setting::getValue('enable_github_login', 'false') === 'true',
            'github_client_id' => Setting::getValue('github_client_id'),
            'github_client_secret' => Setting::getValue('github_client_secret'),

            // Telegram
            'enable_telegram' => Setting::getValue('enable_telegram', 'false') === 'true',
            'telegram_chat_id' => Setting::getValue('telegram_chat_id'),
            'telegram_bot_token' => Setting::getValue('telegram_bot_token'),
            'telegram_notify_upvotes' => Setting::getValue('telegram_notify_upvotes', 'false') === 'true',
            'telegram_webhook' => \App\Actions\Telegram\SetupTelegramWebhook::make()->getInfo(),
        ];
    }
}
