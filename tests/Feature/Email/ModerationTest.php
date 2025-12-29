<?php

declare(strict_types=1);

namespace Tests\Feature\Email;

use App\Actions\Email\ModerateViaEmail;
use App\Actions\Email\SendModerationNotification;
use App\Mail\ModerationNotificationMail;
use App\Models\Comment;
use App\Models\Setting;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_moderation_notification_to_admin(): void
    {
        Mail::fake();

        Setting::setValue('admin_email', 'admin@example.com');

        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'status' => 'pending',
        ]);

        $token = SendModerationNotification::run($comment);

        $this->assertNotNull($token);

        $comment->refresh();
        $this->assertEquals($token, $comment->moderation_token);

        Mail::assertSent(ModerationNotificationMail::class, function ($mail) {
            return $mail->hasTo('admin@example.com');
        });
    }

    public function test_does_not_send_moderation_notification_without_admin_email(): void
    {
        Mail::fake();

        // No admin_email set
        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'status' => 'pending',
        ]);

        $token = SendModerationNotification::run($comment);

        $this->assertNull($token);
        Mail::assertNothingSent();
    }

    public function test_does_not_send_moderation_notification_for_approved_comments(): void
    {
        Mail::fake();

        Setting::setValue('admin_email', 'admin@example.com');

        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'status' => 'approved',
        ]);

        $token = SendModerationNotification::run($comment);

        $this->assertNull($token);
        Mail::assertNothingSent();
    }

    public function test_approve_via_email_with_valid_token(): void
    {
        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'status' => 'pending',
            'moderation_token' => 'valid-mod-token',
        ]);

        $result = ModerateViaEmail::make()->approve($comment, 'valid-mod-token');

        $this->assertTrue($result);

        $comment->refresh();
        $this->assertEquals('approved', $comment->status);
        $this->assertNull($comment->moderation_token);
    }

    public function test_approve_via_email_fails_with_invalid_token(): void
    {
        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'status' => 'pending',
            'moderation_token' => 'correct-token',
        ]);

        $result = ModerateViaEmail::make()->approve($comment, 'wrong-token');

        $this->assertFalse($result);

        $comment->refresh();
        $this->assertEquals('pending', $comment->status);
    }

    public function test_delete_via_email_with_valid_token(): void
    {
        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'status' => 'pending',
            'moderation_token' => 'delete-token',
        ]);

        $result = ModerateViaEmail::make()->delete($comment, 'delete-token');

        $this->assertTrue($result);

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }

    public function test_delete_via_email_fails_with_invalid_token(): void
    {
        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'status' => 'pending',
            'moderation_token' => 'correct-token',
        ]);

        $result = ModerateViaEmail::make()->delete($comment, 'wrong-token');

        $this->assertFalse($result);

        $comment->refresh();
        $this->assertNull($comment->deleted_at);
    }

    public function test_approve_endpoint_redirects_on_success(): void
    {
        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'status' => 'pending',
            'moderation_token' => 'endpoint-token',
        ]);

        $response = $this->get("/moderate/{$comment->id}/approve/endpoint-token");

        $response->assertRedirect('/admin/comments');
        $response->assertSessionHas('success');
    }

    public function test_approve_endpoint_redirects_on_failure(): void
    {
        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'status' => 'pending',
            'moderation_token' => 'correct-token',
        ]);

        $response = $this->get("/moderate/{$comment->id}/approve/wrong-token");

        $response->assertRedirect('/admin/comments');
        $response->assertSessionHas('error');
    }

    public function test_delete_endpoint_redirects_on_success(): void
    {
        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'status' => 'pending',
            'moderation_token' => 'delete-endpoint-token',
        ]);

        $response = $this->get("/moderate/{$comment->id}/delete/delete-endpoint-token");

        $response->assertRedirect('/admin/comments');
        $response->assertSessionHas('success');
    }

    public function test_delete_endpoint_redirects_on_failure(): void
    {
        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'status' => 'pending',
            'moderation_token' => 'correct-token',
        ]);

        $response = $this->get("/moderate/{$comment->id}/delete/wrong-token");

        $response->assertRedirect('/admin/comments');
        $response->assertSessionHas('error');
    }
}
