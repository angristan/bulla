<?php

declare(strict_types=1);

use App\Actions\Admin\SetupAdmin;
use App\Models\Comment;
use App\Models\Thread;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    SetupAdmin::run('admin', 'admin@example.com', 'password123', 'Test Site', 'https://example.com');

    $this->post('/admin/login', [
        'username' => 'admin',
        'password' => 'password123',
    ]);
});

describe('Admin Comments List', function (): void {
    it('shows comments list', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        Comment::create([
            'thread_id' => $thread->id,
            'author' => 'John',
            'body_markdown' => 'Test comment',
            'body_html' => '<p>Test comment</p>',
            'status' => 'approved',
        ]);

        $response = $this->get('/admin/comments');

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Comments/Index')
                ->has('comments.data', 1)
        );
    });

    it('filters by status', function (): void {
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

        $response = $this->get('/admin/comments?status=pending');

        $response->assertInertia(
            fn ($page) => $page
                ->has('comments.data', 1)
                ->where('comments.data.0.status', 'pending')
        );
    });

    it('searches comments', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        Comment::create([
            'thread_id' => $thread->id,
            'author' => 'John',
            'body_markdown' => 'Hello world',
            'body_html' => '<p>Hello world</p>',
            'status' => 'approved',
        ]);
        Comment::create([
            'thread_id' => $thread->id,
            'author' => 'Jane',
            'body_markdown' => 'Goodbye world',
            'body_html' => '<p>Goodbye world</p>',
            'status' => 'approved',
        ]);

        $response = $this->get('/admin/comments?search=Hello');

        $response->assertInertia(
            fn ($page) => $page
                ->has('comments.data', 1)
                ->where('comments.data.0.author', 'John')
        );
    });

    it('paginates comments', function (): void {
        $thread = Thread::create(['uri' => '/test']);

        for ($i = 0; $i < 30; $i++) {
            Comment::create([
                'thread_id' => $thread->id,
                'body_markdown' => "Comment $i",
                'body_html' => "<p>Comment $i</p>",
                'status' => 'approved',
            ]);
        }

        $response = $this->get('/admin/comments?per_page=20');

        $response->assertInertia(
            fn ($page) => $page
                ->has('comments.data', 20)
                ->where('comments.meta.total', 30)
                ->where('comments.meta.last_page', 2)
        );
    });
});

describe('Admin Comment Actions', function (): void {
    it('shows single comment', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $comment = Comment::create([
            'thread_id' => $thread->id,
            'author' => 'John',
            'body_markdown' => 'Test comment',
            'body_html' => '<p>Test comment</p>',
            'status' => 'pending',
        ]);

        $response = $this->get("/admin/comments/{$comment->id}");

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Comments/Show')
                ->where('comment.id', $comment->id)
                ->where('comment.author', 'John')
        );
    });

    it('approves a comment', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $comment = Comment::create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Test',
            'body_html' => '<p>Test</p>',
            'status' => 'pending',
        ]);

        $response = $this->post("/admin/comments/{$comment->id}/approve");

        $response->assertRedirect();
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => 'approved',
        ]);
    });

    it('marks comment as spam', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $comment = Comment::create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Test',
            'body_html' => '<p>Test</p>',
            'status' => 'pending',
        ]);

        $response = $this->post("/admin/comments/{$comment->id}/spam");

        $response->assertRedirect();
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => 'spam',
        ]);
    });

    it('deletes a comment', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $comment = Comment::create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Test',
            'body_html' => '<p>Test</p>',
            'status' => 'pending',
        ]);

        $response = $this->delete("/admin/comments/{$comment->id}");

        $response->assertRedirect('/admin/comments');
        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    });
});
