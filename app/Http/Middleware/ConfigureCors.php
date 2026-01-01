<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class ConfigureCors
{
    /**
     * Handle an incoming request.
     *
     * Set CORS config from admin setting before HandleCors runs.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if settings table doesn't exist yet (e.g., during migrations)
        if (! Schema::hasTable('settings')) {
            return $next($request);
        }

        // Get allowed origins, falling back to site_url for security
        $origins = Setting::getValue('allowed_origins') ?: Setting::getValue('site_url', '');

        if ($origins) {
            $isWildcard = $origins === '*';
            $parsed = $isWildcard
                ? ['*']
                : array_map('trim', explode(',', $origins));
            config(['cors.allowed_origins' => $parsed]);

            // Disable credentials for wildcard origins to prevent CSRF-like attacks
            if ($isWildcard) {
                config(['cors.supports_credentials' => false]);
            }
        }

        return $next($request);
    }
}
