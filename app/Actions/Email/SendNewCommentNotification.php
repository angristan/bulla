<?php

declare(strict_types=1);

namespace App\Actions\Email;

use App\Mail\NewCommentNotificationMail;
use App\Models\Comment;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Lorisleiva\Actions\Concerns\AsAction;

class SendNewCommentNotification
{
    use AsAction;

    public function handle(Comment $comment): ?string
    {
        $adminEmail = Setting::getValue('admin_email');

        if (! $adminEmail) {
            return null;
        }

        // Don't notify for admin comments
        if ($comment->is_admin) {
            return null;
        }

        $mail = new NewCommentNotificationMail($comment);

        // Store the token on the comment for validation (for delete action)
        $comment->update(['moderation_token' => $mail->moderationToken]);

        Mail::to($adminEmail)->send($mail);

        return $mail->moderationToken;
    }
}
