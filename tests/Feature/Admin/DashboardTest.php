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

describe('Admin Dashboard', function (): void {
    it('shows dashboard with stats', function (): void {
        $response = $this->get('/admin');

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Dashboard')
                ->has('stats')
                ->has('stats.total_comments')
                ->has('stats.pending_comments')
                ->has('stats.approved_comments')
                ->has('stats.spam_comments')
                ->has('stats.recent_comments')
                ->has('stats.comments_this_week')
        );
    });

    it('shows correct comment counts', function (): void {
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

        $response = $this->get('/admin');

        $response->assertInertia(
            fn ($page) => $page
                ->where('stats.total_comments', 3)
                ->where('stats.approved_comments', 1)
                ->where('stats.pending_comments', 1)
                ->where('stats.spam_comments', 1)
        );
    });

    it('shows recent comments', function (): void {
        $thread = Thread::create(['uri' => '/test']);

        for ($i = 0; $i < 15; $i++) {
            Comment::create([
                'thread_id' => $thread->id,
                'body_markdown' => "Comment $i",
                'body_html' => "<p>Comment $i</p>",
                'status' => 'approved',
            ]);
        }

        $response = $this->get('/admin');

        $response->assertInertia(
            fn ($page) => $page
                ->has('stats.recent_comments', 10) // Max 10 recent
        );
    });
});
