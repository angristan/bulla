<?php

declare(strict_types=1);

namespace App\Actions\Email;

use App\Mail\NewCommentNotificationMail;
use App\Models\Comment;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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

        // Generate token and store on comment before queuing
        $moderationToken = Str::random(64);
        $comment->update(['moderation_token' => $moderationToken]);

        Mail::to($adminEmail)->queue(
            new NewCommentNotificationMail($comment, $moderationToken)
        );

        return $moderationToken;
    }
}
