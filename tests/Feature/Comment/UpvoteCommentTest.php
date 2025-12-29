<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Thread;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('POST /api/comments/{id}/upvote', function (): void {
    it('increments upvote count', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $comment = Comment::create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Test',
            'body_html' => '<p>Test</p>',
            'status' => 'approved',
            'upvotes' => 0,
        ]);

        $response = $this->postJson("/api/comments/{$comment->id}/upvote");

        $response->assertOk()
            ->assertJson(['upvotes' => 1]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'upvotes' => 1,
        ]);
    });

    it('prevents duplicate votes from same IP', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $comment = Comment::create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Test',
            'body_html' => '<p>Test</p>',
            'status' => 'approved',
            'upvotes' => 0,
        ]);

        // First vote
        $this->postJson("/api/comments/{$comment->id}/upvote")
            ->assertOk()
            ->assertJson(['upvotes' => 1]);

        // Second vote from same IP
        $response = $this->postJson("/api/comments/{$comment->id}/upvote");

        $response->assertStatus(409)
            ->assertJson(['error' => 'Already voted.']);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'upvotes' => 1,
        ]);
    });

    it('allows votes from different IPs', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $comment = Comment::create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Test',
            'body_html' => '<p>Test</p>',
            'status' => 'approved',
            'upvotes' => 0,
        ]);

        // Vote from first IP
        $this->postJson(
            "/api/comments/{$comment->id}/upvote",
            [],
            ['REMOTE_ADDR' => '192.168.1.1']
        )->assertOk();

        // Vote from second IP
        $this->postJson(
            "/api/comments/{$comment->id}/upvote",
            [],
            ['REMOTE_ADDR' => '192.168.1.2']
        )->assertOk();

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'upvotes' => 2,
        ]);
    });

    it('stores votes in bloom filter', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $comment = Comment::create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Test',
            'body_html' => '<p>Test</p>',
            'status' => 'approved',
            'upvotes' => 0,
            'voters_bloom' => null,
        ]);

        $this->postJson("/api/comments/{$comment->id}/upvote");

        $comment->refresh();
        expect($comment->voters_bloom)->not->toBeNull();
    });
});
