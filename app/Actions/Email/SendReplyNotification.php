<?php

declare(strict_types=1);

namespace App\Actions\Email;

use App\Mail\ReplyNotificationMail;
use App\Models\Comment;
use App\Models\NotificationSubscription;
use Illuminate\Support\Facades\Mail;
use Lorisleiva\Actions\Concerns\AsAction;

class SendReplyNotification
{
    use AsAction;

    public function handle(Comment $reply): void
    {
        // Only send if this is a reply
        if (! $reply->parent_id) {
            return;
        }

        $parentComment = $reply->parent;
        if (! $parentComment) {
            return;
        }

        // Check if parent author has email and wants notifications
        if (! $parentComment->email || ! $parentComment->notify_replies) {
            return;
        }

        // Don't notify if replying to own comment
        if ($parentComment->email === $reply->email) {
            return;
        }

        // Get or create subscription
        $subscription = NotificationSubscription::firstOrCreate(
            [
                'email' => $parentComment->email,
                'comment_id' => $parentComment->id,
            ],
            [
                'unsubscribe_token' => \Illuminate\Support\Str::random(64),
            ]
        );

        // Don't send if unsubscribed
        if ($subscription->unsubscribed_at) {
            return;
        }

        Mail::to($parentComment->email)->queue(
            new ReplyNotificationMail($reply, $parentComment, $subscription)
        );
    }
}
