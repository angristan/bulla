<?php

declare(strict_types=1);

namespace App\Actions\Email;

use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Lorisleiva\Actions\Concerns\AsAction;

class SendTestEmail
{
    use AsAction;

    /**
     * Send a test email to the admin.
     *
     * @return array{success: bool, message: string}
     */
    public function handle(): array
    {
        $adminEmail = Setting::getValue('admin_email');

        if (! $adminEmail) {
            return ['success' => false, 'message' => 'Admin email not configured in General settings'];
        }

        $smtpHost = Setting::getValue('smtp_host');

        if (! $smtpHost) {
            return ['success' => false, 'message' => 'SMTP host not configured'];
        }

        $fromAddress = Setting::getValue('smtp_from_address');

        if (! $fromAddress) {
            return ['success' => false, 'message' => 'From address not configured'];
        }

        $siteName = Setting::getValue('site_name', 'Bulla');

        try {
            Mail::raw(
                "Test email from {$siteName}\n\nIf you received this email, your email configuration is working correctly!",
                function ($message) use ($adminEmail, $siteName): void {
                    $message->to($adminEmail)
                        ->subject("Test Email from {$siteName}");
                }
            );

            return ['success' => true, 'message' => "Test email sent to {$adminEmail}"];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
