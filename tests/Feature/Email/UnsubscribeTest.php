<?php

declare(strict_types=1);

namespace Tests\Feature\Email;

use App\Actions\Email\Unsubscribe;
use App\Models\Comment;
use App\Models\NotificationSubscription;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnsubscribeTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsubscribes_successfully(): void
    {
        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create(['thread_id' => $thread->id]);

        $subscription = NotificationSubscription::create([
            'email' => 'test@example.com',
            'comment_id' => $comment->id,
            'unsubscribe_token' => 'unsub-token-123',
        ]);

        $result = Unsubscribe::run('unsub-token-123');

        $this->assertTrue($result);

        $subscription->refresh();
        $this->assertNotNull($subscription->unsubscribed_at);
    }

    public function test_unsubscribe_fails_with_invalid_token(): void
    {
        $result = Unsubscribe::run('invalid-token');

        $this->assertFalse($result);
    }

    public function test_unsubscribe_succeeds_if_already_unsubscribed(): void
    {
        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create(['thread_id' => $thread->id]);

        NotificationSubscription::create([
            'email' => 'test@example.com',
            'comment_id' => $comment->id,
            'unsubscribe_token' => 'already-unsub-token',
            'unsubscribed_at' => now()->subDay(),
        ]);

        $result = Unsubscribe::run('already-unsub-token');

        $this->assertTrue($result);
    }

    public function test_unsubscribe_endpoint_redirects_on_success(): void
    {
        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create(['thread_id' => $thread->id]);

        NotificationSubscription::create([
            'email' => 'test@example.com',
            'comment_id' => $comment->id,
            'unsubscribe_token' => 'valid-unsub-token',
        ]);

        $response = $this->get('/unsubscribe/valid-unsub-token');

        $response->assertRedirect('/');
        $response->assertSessionHas('success');
    }

    public function test_unsubscribe_endpoint_redirects_on_failure(): void
    {
        $response = $this->get('/unsubscribe/invalid-token');

        $response->assertRedirect('/');
        $response->assertSessionHas('error');
    }
}
