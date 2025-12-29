<?php

declare(strict_types=1);

namespace App\Actions\Email;

use App\Mail\ModerationNotificationMail;
use App\Models\Comment;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Lorisleiva\Actions\Concerns\AsAction;

class SendModerationNotification
{
    use AsAction;

    public function handle(Comment $comment): ?string
    {
        $adminEmail = Setting::getValue('admin_email');

        if (! $adminEmail) {
            return null;
        }

        // Only send for pending comments
        if ($comment->status !== 'pending') {
            return null;
        }

        $mail = new ModerationNotificationMail($comment);

        // Store the token on the comment for validation
        $comment->update(['moderation_token' => $mail->moderationToken]);

        Mail::to($adminEmail)->send($mail);

        return $mail->moderationToken;
    }
}
