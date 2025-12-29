<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Setting;
use App\Models\Thread;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('POST /api/threads/{uri}/comments', function (): void {
    it('creates a comment', function (): void {
        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'This is a test comment',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'author',
                'body_html',
                'status',
                'upvotes',
                'created_at',
                'edit_token',
                'edit_token_expires_at',
            ]);

        $this->assertDatabaseHas('comments', [
            'body_markdown' => 'This is a test comment',
        ]);
    });

    it('creates thread if not exists', function (): void {
        $this->postJson('/api/threads/new-thread/comments', [
            'body' => 'Test',
        ]);

        $this->assertDatabaseHas('threads', [
            'uri' => '/new-thread',
        ]);
    });

    it('converts markdown to HTML', function (): void {
        $response = $this->postJson('/api/threads/test/comments', [
            'body' => '**bold** and *italic*',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('comments', [
            'body_markdown' => '**bold** and *italic*',
        ]);

        $comment = Comment::first();
        expect($comment->body_html)->toContain('<strong>bold</strong>');
        expect($comment->body_html)->toContain('<em>italic</em>');
    });

    it('saves author info', function (): void {
        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Test',
            'author' => 'John Doe',
            'email' => 'john@example.com',
            'website' => 'https://example.com',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('comments', [
            'author' => 'John Doe',
            'email' => 'john@example.com',
            'website' => 'https://example.com',
        ]);
    });

    it('adds https to website without protocol', function (): void {
        $this->postJson('/api/threads/test/comments', [
            'body' => 'Test',
            'website' => 'example.com',
        ]);

        $this->assertDatabaseHas('comments', [
            'website' => 'https://example.com',
        ]);
    });

    it('saves thread title and URL', function (): void {
        $this->postJson('/api/threads/test/comments', [
            'body' => 'Test',
            'title' => 'My Blog Post',
            'url' => 'https://myblog.com/test',
        ]);

        $this->assertDatabaseHas('threads', [
            'uri' => '/test',
            'title' => 'My Blog Post',
            'url' => 'https://myblog.com/test',
        ]);
    });

    it('allows nested comments', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $parent = Comment::create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Parent',
            'body_html' => '<p>Parent</p>',
            'status' => 'approved',
        ]);

        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Reply',
            'parent_id' => $parent->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('comments', [
            'body_markdown' => 'Reply',
            'parent_id' => $parent->id,
        ]);
    });

    it('validates body is required', function (): void {
        $response = $this->postJson('/api/threads/test/comments', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    });

    it('validates body max length', function (): void {
        $response = $this->postJson('/api/threads/test/comments', [
            'body' => str_repeat('a', 70000),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    });

    it('validates parent_id exists', function (): void {
        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Test',
            'parent_id' => 999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);
    });

    it('returns edit token for editing', function (): void {
        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Test',
        ]);

        $response->assertStatus(201);

        $data = $response->json();
        expect($data['edit_token'])->toBeString();
        expect(strlen($data['edit_token']))->toBe(64);
    });

    it('anonymizes IP address', function (): void {
        $this->postJson('/api/threads/test/comments', [
            'body' => 'Test',
        ], ['REMOTE_ADDR' => '192.168.1.123']);

        $comment = Comment::first();
        expect($comment->remote_addr)->toBe('192.168.1.0');
    });

    describe('moderation', function (): void {
        it('auto-approves when moderation is disabled', function (): void {
            Setting::setValue('moderation_mode', 'none');

            $response = $this->postJson('/api/threads/test/comments', [
                'body' => 'Test',
            ]);

            $response->assertStatus(201)
                ->assertJsonPath('status', 'approved');
        });

        it('sets pending when moderation is all', function (): void {
            Setting::setValue('moderation_mode', 'all');

            $response = $this->postJson('/api/threads/test/comments', [
                'body' => 'Test',
            ]);

            $response->assertStatus(201)
                ->assertJsonPath('status', 'pending');
        });
    });
});
