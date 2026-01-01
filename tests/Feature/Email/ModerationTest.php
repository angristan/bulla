<?php

declare(strict_types=1);

namespace Tests\Feature\Email;

use App\Actions\Email\ModerateViaEmail;
use App\Models\Comment;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModerationTest extends TestCase
{
    use RefreshDatabase;

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
