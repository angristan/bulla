<?php

declare(strict_types=1);

namespace Tests\Feature\Email;

use App\Actions\Email\SendNewCommentNotification;
use App\Mail\NewCommentNotificationMail;
use App\Models\Comment;
use App\Models\Setting;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewCommentNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_notification_to_admin(): void
    {
        Mail::fake();

        Setting::setValue('admin_email', 'admin@example.com');

        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'status' => 'approved',
            'is_admin' => false,
        ]);

        $token = SendNewCommentNotification::run($comment);

        $this->assertNotNull($token);

        Mail::assertQueued(NewCommentNotificationMail::class, function ($mail) {
            return $mail->hasTo('admin@example.com');
        });
    }

    public function test_does_not_send_notification_without_admin_email(): void
    {
        Mail::fake();

        // No admin_email set
        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'is_admin' => false,
        ]);

        $token = SendNewCommentNotification::run($comment);

        $this->assertNull($token);
        Mail::assertNothingQueued();
    }

    public function test_does_not_send_notification_for_admin_comments(): void
    {
        Mail::fake();

        Setting::setValue('admin_email', 'admin@example.com');

        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'is_admin' => true,
        ]);

        $token = SendNewCommentNotification::run($comment);

        $this->assertNull($token);
        Mail::assertNothingQueued();
    }

    public function test_stores_moderation_token_on_comment(): void
    {
        Mail::fake();

        Setting::setValue('admin_email', 'admin@example.com');

        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'is_admin' => false,
            'moderation_token' => null,
        ]);

        $token = SendNewCommentNotification::run($comment);

        $comment->refresh();
        $this->assertEquals($token, $comment->moderation_token);
        $this->assertEquals(64, strlen($token));
    }

    public function test_subject_includes_pending_for_pending_comments(): void
    {
        Mail::fake();

        Setting::setValue('admin_email', 'admin@example.com');
        Setting::setValue('site_name', 'My Blog');

        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'status' => 'pending',
            'is_admin' => false,
        ]);

        SendNewCommentNotification::run($comment);

        Mail::assertQueued(NewCommentNotificationMail::class, function ($mail) {
            return $mail->envelope()->subject === 'New comment (pending) - My Blog';
        });
    }

    public function test_subject_excludes_pending_for_approved_comments(): void
    {
        Mail::fake();

        Setting::setValue('admin_email', 'admin@example.com');
        Setting::setValue('site_name', 'My Blog');

        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'status' => 'approved',
            'is_admin' => false,
        ]);

        SendNewCommentNotification::run($comment);

        Mail::assertQueued(NewCommentNotificationMail::class, function ($mail) {
            return $mail->envelope()->subject === 'New comment - My Blog';
        });
    }
}
