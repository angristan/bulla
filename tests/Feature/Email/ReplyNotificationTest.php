<?php

declare(strict_types=1);

namespace Tests\Feature\Email;

use App\Actions\Email\SendReplyNotification;
use App\Mail\ReplyNotificationMail;
use App\Models\Comment;
use App\Models\NotificationSubscription;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReplyNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_reply_notification_to_parent_author(): void
    {
        Mail::fake();

        $thread = Thread::factory()->create();
        $parentComment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'email' => 'parent@example.com',
            'notify_replies' => true,
        ]);

        $reply = Comment::factory()->create([
            'thread_id' => $thread->id,
            'parent_id' => $parentComment->id,
            'email' => 'replier@example.com',
        ]);

        SendReplyNotification::run($reply);

        Mail::assertQueued(ReplyNotificationMail::class, function ($mail) use ($parentComment) {
            return $mail->hasTo($parentComment->email);
        });
    }

    public function test_does_not_send_notification_if_parent_disabled_notifications(): void
    {
        Mail::fake();

        $thread = Thread::factory()->create();
        $parentComment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'email' => 'parent@example.com',
            'notify_replies' => false,
        ]);

        $reply = Comment::factory()->create([
            'thread_id' => $thread->id,
            'parent_id' => $parentComment->id,
        ]);

        SendReplyNotification::run($reply);

        Mail::assertNothingQueued();
    }

    public function test_does_not_send_notification_if_parent_has_no_email(): void
    {
        Mail::fake();

        $thread = Thread::factory()->create();
        $parentComment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'email' => null,
            'notify_replies' => true,
        ]);

        $reply = Comment::factory()->create([
            'thread_id' => $thread->id,
            'parent_id' => $parentComment->id,
        ]);

        SendReplyNotification::run($reply);

        Mail::assertNothingQueued();
    }

    public function test_does_not_send_notification_for_top_level_comment(): void
    {
        Mail::fake();

        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'parent_id' => null,
        ]);

        SendReplyNotification::run($comment);

        Mail::assertNothingQueued();
    }

    public function test_does_not_send_notification_for_self_reply(): void
    {
        Mail::fake();

        $thread = Thread::factory()->create();
        $parentComment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'email' => 'same@example.com',
            'notify_replies' => true,
        ]);

        $reply = Comment::factory()->create([
            'thread_id' => $thread->id,
            'parent_id' => $parentComment->id,
            'email' => 'same@example.com',
        ]);

        SendReplyNotification::run($reply);

        Mail::assertNothingQueued();
    }

    public function test_does_not_send_notification_if_unsubscribed(): void
    {
        Mail::fake();

        $thread = Thread::factory()->create();
        $parentComment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'email' => 'parent@example.com',
            'notify_replies' => true,
        ]);

        // Create unsubscribed subscription
        NotificationSubscription::create([
            'email' => 'parent@example.com',
            'comment_id' => $parentComment->id,
            'unsubscribe_token' => 'unsub-token',
            'unsubscribed_at' => now(),
        ]);

        $reply = Comment::factory()->create([
            'thread_id' => $thread->id,
            'parent_id' => $parentComment->id,
            'email' => 'replier@example.com',
        ]);

        SendReplyNotification::run($reply);

        Mail::assertNothingQueued();
    }

    public function test_creates_subscription_when_sending_notification(): void
    {
        Mail::fake();

        $thread = Thread::factory()->create();
        $parentComment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'email' => 'parent@example.com',
            'notify_replies' => true,
        ]);

        $reply = Comment::factory()->create([
            'thread_id' => $thread->id,
            'parent_id' => $parentComment->id,
            'email' => 'replier@example.com',
        ]);

        SendReplyNotification::run($reply);

        $this->assertDatabaseHas('notification_subscriptions', [
            'email' => 'parent@example.com',
            'comment_id' => $parentComment->id,
            'unsubscribed_at' => null,
        ]);
    }
}
