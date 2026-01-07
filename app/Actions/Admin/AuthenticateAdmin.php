<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\Passkey;
use App\Models\Setting;
use Lorisleiva\Actions\Concerns\AsAction;

class AuthenticateAdmin
{
    use AsAction;

    /**
     * Check if admin is set up (has at least one passkey registered).
     */
    public static function isSetup(): bool
    {
        return Setting::getValue('admin_username') !== null
            && Passkey::exists();
    }

    /**
     * Check if passkeys are configured.
     */
    public static function hasPasskeys(): bool
    {
        return Passkey::exists();
    }
}
