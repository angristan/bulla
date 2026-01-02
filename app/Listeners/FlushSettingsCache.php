<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Setting;

class FlushSettingsCache
{
    public function handle(): void
    {
        Setting::flushCache();
    }
}
