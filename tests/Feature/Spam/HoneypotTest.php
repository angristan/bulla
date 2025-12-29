<?php

declare(strict_types=1);

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Honeypot spam protection', function (): void {
    it('allows comments without honeypot field', function (): void {
        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Test comment',
        ]);

        $response->assertStatus(201);
    });

    it('allows comments with empty honeypot', function (): void {
        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Test comment',
            'honeypot' => '',
        ]);

        $response->assertStatus(201);
    });

    it('allows comments with null honeypot', function (): void {
        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Test comment',
            'honeypot' => null,
        ]);

        $response->assertStatus(201);
    });

    it('rejects comments with filled honeypot', function (): void {
        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Spam comment',
            'honeypot' => 'I am a bot',
        ]);

        $response->assertStatus(422)
            ->assertJson(['error' => 'Invalid submission.']);

        $this->assertDatabaseMissing('comments', [
            'body_markdown' => 'Spam comment',
        ]);
    });
});
