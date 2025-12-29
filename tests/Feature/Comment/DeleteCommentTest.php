<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Thread;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('DELETE /api/comments/{id}', function (): void {
    it('deletes comment with valid token', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $comment = Comment::create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Test',
            'body_html' => '<p>Test</p>',
            'status' => 'approved',
            'edit_token' => 'valid_token_1234567890123456789012345678901234567890123456789012',
            'edit_token_expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->deleteJson("/api/comments/{$comment->id}", [
            'edit_token' => 'valid_token_1234567890123456789012345678901234567890123456789012',
        ]);

        $response->assertOk()
            ->assertJson(['deleted' => true]);

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
            'deleted_at' => null,
        ]);
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

        $response = $this->deleteJson("/api/comments/{$comment->id}", [
            'edit_token' => 'wrong_token',
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
        ]);
    });

    it('soft deletes comment with replies', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $parent = Comment::create([
            'thread_id' => $thread->id,
            'author' => 'Author',
            'body_markdown' => 'Parent comment',
            'body_html' => '<p>Parent comment</p>',
            'status' => 'approved',
            'edit_token' => 'valid_token_1234567890123456789012345678901234567890123456789012',
            'edit_token_expires_at' => now()->addMinutes(15),
        ]);
        Comment::create([
            'thread_id' => $thread->id,
            'parent_id' => $parent->id,
            'body_markdown' => 'Reply',
            'body_html' => '<p>Reply</p>',
            'status' => 'approved',
        ]);

        $response = $this->deleteJson("/api/comments/{$parent->id}", [
            'edit_token' => 'valid_token_1234567890123456789012345678901234567890123456789012',
        ]);

        $response->assertOk();

        // Parent should be soft deleted with cleared content
        $this->assertSoftDeleted('comments', [
            'id' => $parent->id,
        ]);

        $parent->refresh();
        expect($parent->body_markdown)->toBe('');
        expect($parent->body_html)->toBe('');
        expect($parent->author)->toBeNull();
        expect($parent->status)->toBe('deleted');

        // Reply should still exist
        $this->assertDatabaseHas('comments', [
            'parent_id' => $parent->id,
            'body_markdown' => 'Reply',
        ]);
    });

    it('validates edit_token is required', function (): void {
        $thread = Thread::create(['uri' => '/test']);
        $comment = Comment::create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Test',
            'body_html' => '<p>Test</p>',
            'status' => 'approved',
        ]);

        $response = $this->deleteJson("/api/comments/{$comment->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['edit_token']);
    });
});
