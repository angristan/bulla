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

        if ($origins = Setting::getValue('allowed_origins')) {
            $parsed = $origins === '*'
                ? ['*']
                : array_map('trim', explode(',', $origins));
            config(['cors.allowed_origins' => $parsed]);
        }

        return $next($request);
    }
}
