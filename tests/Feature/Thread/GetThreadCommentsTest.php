<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Thread;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('GET /api/threads/{uri}/comments', function (): void {
    it('returns empty array for thread without comments', function (): void {
        $response = $this->getJson('/api/threads/test-page/comments');

        $response->assertOk()
            ->assertJsonStructure([
                'thread' => ['id', 'uri', 'title'],
                'comments',
                'total',
            ])
            ->assertJson([
                'comments' => [],
                'total' => 0,
            ]);
    });

    it('does not create thread on GET', function (): void {
        $response = $this->getJson('/api/threads/new-page/comments');

        $response->assertOk()
            ->assertJson([
                'thread' => ['id' => null, 'uri' => '/new-page'],
                'comments' => [],
                'total' => 0,
            ]);

        $this->assertDatabaseMissing('threads', [
            'uri' => '/new-page',
        ]);
    });

    it('normalizes URI with slashes', function (): void {
        Thread::create(['uri' => '/test-page']);

        $response1 = $this->getJson('/api/threads//test-page//comments');
        $response2 = $this->getJson('/api/threads/test-page/comments');

        $response1->assertOk();
        $response2->assertOk();
        $this->assertDatabaseCount('threads', 1);
    });

    it('returns approved comments only', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        Comment::create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Approved',
            'body_html' => '<p>Approved</p>',
            'status' => 'approved',
        ]);
        Comment::create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Pending',
            'body_html' => '<p>Pending</p>',
            'status' => 'pending',
        ]);
        Comment::create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Spam',
            'body_html' => '<p>Spam</p>',
            'status' => 'spam',
        ]);

        $response = $this->getJson('/api/threads/test/comments');

        $response->assertOk()
            ->assertJson(['total' => 1])
            ->assertJsonCount(1, 'comments');
    });

    it('returns nested comments', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $parent = Comment::create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Parent',
            'body_html' => '<p>Parent</p>',
            'status' => 'approved',
        ]);
        Comment::create([
            'thread_id' => $thread->id,
            'parent_id' => $parent->id,
            'body_markdown' => 'Reply',
            'body_html' => '<p>Reply</p>',
            'status' => 'approved',
        ]);

        $response = $this->getJson('/api/threads/test/comments');

        $response->assertOk()
            ->assertJsonCount(1, 'comments')
            ->assertJsonCount(1, 'comments.0.replies');
    });

    it('returns parent_author for nested comments', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $parent = Comment::create([
            'thread_id' => $thread->id,
            'author' => 'John Doe',
            'body_markdown' => 'Parent',
            'body_html' => '<p>Parent</p>',
            'status' => 'approved',
            'depth' => 0,
        ]);
        Comment::create([
            'thread_id' => $thread->id,
            'parent_id' => $parent->id,
            'author' => 'Jane Doe',
            'body_markdown' => 'Reply',
            'body_html' => '<p>Reply</p>',
            'status' => 'approved',
            'depth' => 1,
        ]);

        $response = $this->getJson('/api/threads/test/comments');

        $response->assertOk()
            ->assertJsonPath('comments.0.parent_author', null)
            ->assertJsonPath('comments.0.replies.0.parent_author', 'John Doe');
    });

    it('returns depth for comments', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $parent = Comment::create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Parent',
            'body_html' => '<p>Parent</p>',
            'status' => 'approved',
            'depth' => 0,
        ]);
        Comment::create([
            'thread_id' => $thread->id,
            'parent_id' => $parent->id,
            'body_markdown' => 'Reply',
            'body_html' => '<p>Reply</p>',
            'status' => 'approved',
            'depth' => 1,
        ]);

        $response = $this->getJson('/api/threads/test/comments');

        $response->assertOk()
            ->assertJsonPath('comments.0.depth', 0)
            ->assertJsonPath('comments.0.replies.0.depth', 1);
    });

    it('returns comment metadata', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        Comment::create([
            'thread_id' => $thread->id,
            'author' => 'John',
            'email' => 'john@example.com',
            'email_verified' => true,
            'is_admin' => false,
            'website' => 'https://example.com',
            'body_markdown' => 'Test',
            'body_html' => '<p>Test</p>',
            'status' => 'approved',
            'upvotes' => 5,
        ]);

        $response = $this->getJson('/api/threads/test/comments');

        $response->assertOk()
            ->assertJsonPath('comments.0.author', 'John')
            ->assertJsonPath('comments.0.email_verified', true)
            ->assertJsonPath('comments.0.is_admin', false)
            ->assertJsonPath('comments.0.website', 'https://example.com')
            ->assertJsonPath('comments.0.upvotes', 5);
    });

    it('handles URL-encoded URIs', function (): void {
        $uri = '/blog/my-post';
        Thread::create(['uri' => $uri]);
        $encoded = urlencode($uri);

        $response = $this->getJson("/api/threads/{$encoded}/comments");

        $response->assertOk()
            ->assertJsonPath('thread.uri', $uri);
    });

    it('returns admin display name and email from settings', function (): void {
        \App\Models\Setting::setValue('admin_display_name', 'Site Admin');
        \App\Models\Setting::setValue('admin_email', 'admin@example.com');

        $thread = Thread::create(['uri' => '/test']);
        Comment::create([
            'thread_id' => $thread->id,
            'is_admin' => true,
            'body_markdown' => 'Admin comment',
            'body_html' => '<p>Admin comment</p>',
            'status' => 'approved',
        ]);

        $response = $this->getJson('/api/threads/test/comments');

        $response->assertOk()
            ->assertJsonPath('comments.0.author', 'Site Admin')
            ->assertJsonPath('comments.0.is_admin', true);
    });

    it('uses default admin name when setting not configured', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        Comment::create([
            'thread_id' => $thread->id,
            'is_admin' => true,
            'body_markdown' => 'Admin comment',
            'body_html' => '<p>Admin comment</p>',
            'status' => 'approved',
        ]);

        $response = $this->getJson('/api/threads/test/comments');

        $response->assertOk()
            ->assertJsonPath('comments.0.author', 'Admin')
            ->assertJsonPath('comments.0.is_admin', true);
    });

    it('returns original author for non-admin comments', function (): void {
        \App\Models\Setting::setValue('admin_display_name', 'Site Admin');

        $thread = Thread::create(['uri' => '/test']);
        Comment::create([
            'thread_id' => $thread->id,
            'author' => 'Regular User',
            'is_admin' => false,
            'body_markdown' => 'User comment',
            'body_html' => '<p>User comment</p>',
            'status' => 'approved',
        ]);

        $response = $this->getJson('/api/threads/test/comments');

        $response->assertOk()
            ->assertJsonPath('comments.0.author', 'Regular User')
            ->assertJsonPath('comments.0.is_admin', false);
    });
});
