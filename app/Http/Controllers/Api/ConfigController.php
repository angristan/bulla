<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Spam\GenerateTimestamp;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class ConfigController extends Controller
{
    /**
     * Get client configuration.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'site_name' => Setting::getValue('site_name', 'Opaska'),
            'require_author' => Setting::getValue('require_author', 'false') === 'true',
            'require_email' => Setting::getValue('require_email', 'false') === 'true',
            'moderation_mode' => Setting::getValue('moderation_mode', 'none'),
            'max_depth' => (int) Setting::getValue('max_depth', '3'),
            'edit_window_minutes' => (int) Setting::getValue('edit_window_minutes', '15'),
            'timestamp' => GenerateTimestamp::run(),
        ]);
    }
}
