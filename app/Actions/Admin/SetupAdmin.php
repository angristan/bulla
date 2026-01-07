<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\Setting;
use Lorisleiva\Actions\Concerns\AsAction;

class SetupAdmin
{
    use AsAction;

    /**
     * Set up the admin account (passkey is registered separately).
     */
    public function handle(
        string $username,
        string $email,
        string $siteName,
        string $siteUrl
    ): void {
        Setting::setValue('admin_username', $username);
        Setting::setValue('admin_email', $email);
        Setting::setValue('site_name', $siteName);
        Setting::setValue('site_url', $siteUrl);

        // Set default settings
        Setting::setValue('moderation_mode', 'none');
        Setting::setValue('require_author', 'false');
        Setting::setValue('require_email', 'false');
        Setting::setValue('max_depth', '3');
        Setting::setValue('edit_window_minutes', '15');
        Setting::setValue('rate_limit_per_minute', '5');
        Setting::setValue('spam_min_time_seconds', '3');
        Setting::setValue('setup_complete', 'true');
    }

    /**
     * Check if setup is complete.
     */
    public static function isComplete(): bool
    {
        return Setting::getValue('setup_complete') === 'true';
    }
}
