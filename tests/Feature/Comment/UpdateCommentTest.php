<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Thread;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('PUT /api/comments/{id}', function (): void {
    it('updates comment body with valid token', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $comment = Comment::create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Original',
            'body_html' => '<p>Original</p>',
            'status' => 'approved',
            'edit_token' => 'valid_token_1234567890123456789012345678901234567890123456789012',
            'edit_token_expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->putJson("/api/comments/{$comment->id}", [
            'body' => 'Updated body',
            'edit_token' => 'valid_token_1234567890123456789012345678901234567890123456789012',
        ]);

        $response->assertOk()
            ->assertJsonPath('body_markdown', 'Updated body');

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'body_markdown' => 'Updated body',
        ]);
    });

    it('updates author with valid token', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $comment = Comment::create([
            'thread_id' => $thread->id,
            'author' => 'Original Author',
            'body_markdown' => 'Test',
            'body_html' => '<p>Test</p>',
            'status' => 'approved',
            'edit_token' => 'valid_token_1234567890123456789012345678901234567890123456789012',
            'edit_token_expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->putJson("/api/comments/{$comment->id}", [
            'author' => 'New Author',
            'edit_token' => 'valid_token_1234567890123456789012345678901234567890123456789012',
        ]);

        $response->assertOk()
            ->assertJsonPath('author', 'New Author');
    });

    it('rejects invalid token', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $comment = Comment::create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Test',
            'body_html' => '<p>Test</p>',
            'status' => 'approved',
            'edit_token' => 'valid_token_1234567890123456789012345678901234567890123456789012',
            'edit_token_expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->putJson("/api/comments/{$comment->id}", [
            'body' => 'Hacked',
            'edit_token' => 'wrong_token',
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'body_markdown' => 'Test',
        ]);
    });

    it('rejects expired token', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $comment = Comment::create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Test',
            'body_html' => '<p>Test</p>',
            'status' => 'approved',
            'edit_token' => 'valid_token_1234567890123456789012345678901234567890123456789012',
            'edit_token_expires_at' => now()->subMinutes(1),
        ]);

        $response = $this->putJson("/api/comments/{$comment->id}", [
            'body' => 'Updated',
            'edit_token' => 'valid_token_1234567890123456789012345678901234567890123456789012',
        ]);

        $response->assertStatus(403);
    });

    it('validates edit_token is required', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $comment = Comment::create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Test',
            'body_html' => '<p>Test</p>',
            'status' => 'approved',
        ]);

        $response = $this->putJson("/api/comments/{$comment->id}", [
            'body' => 'Updated',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['edit_token']);
    });

    it('converts new body to HTML', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $comment = Comment::create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Test',
            'body_html' => '<p>Test</p>',
            'status' => 'approved',
            'edit_token' => 'valid_token_1234567890123456789012345678901234567890123456789012',
            'edit_token_expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->putJson("/api/comments/{$comment->id}", [
            'body' => '**Bold text**',
            'edit_token' => 'valid_token_1234567890123456789012345678901234567890123456789012',
        ]);

        $response->assertOk();

        $comment->refresh();
        expect($comment->body_html)->toContain('<strong>Bold text</strong>');
    });
});
