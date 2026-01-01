<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Setting;
use App\Support\ImageProxy;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Onliner\ImgProxy\UrlBuilder;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (ImageProxy::isEnabled()) {
            $this->app->bind(UrlBuilder::class, function () {
                return UrlBuilder::signed(
                    key: config('services.imgproxy.key'),
                    salt: config('services.imgproxy.salt')
                );
            });
        }

        $this->configureMailFromDatabase();
    }

    /**
     * Configure mail settings from database if email is enabled.
     */
    private function configureMailFromDatabase(): void
    {
        // Skip if database isn't ready (migrations, etc.)
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }
        } catch (\Exception) {
            return;
        }

        // Only override if email is enabled in settings
        if (Setting::getValue('enable_email', 'false') !== 'true') {
            return;
        }

        $smtpHost = Setting::getValue('smtp_host');
        if (! $smtpHost) {
            return;
        }

        $encryption = Setting::getValue('smtp_encryption', 'tls');

        // Map encryption setting to Symfony mailer scheme
        // tls = STARTTLS (port 587) - use null scheme, transport auto-upgrades
        // ssl = implicit TLS (port 465) - use smtps scheme
        // none = no encryption
        $scheme = match ($encryption) {
            'ssl' => 'smtps',
            default => null, // tls/none: let transport handle it
        };

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $smtpHost,
            'mail.mailers.smtp.port' => (int) Setting::getValue('smtp_port', '587'),
            'mail.mailers.smtp.username' => Setting::getValue('smtp_username'),
            'mail.mailers.smtp.password' => Setting::getValue('smtp_password'),
            'mail.mailers.smtp.scheme' => $scheme,
            'mail.from.address' => Setting::getValue('smtp_from_address'),
            'mail.from.name' => Setting::getValue('smtp_from_name', Setting::getValue('site_name', 'Bulla')),
        ]);
    }
}
