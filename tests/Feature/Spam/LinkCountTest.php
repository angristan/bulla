<?php

declare(strict_types=1);

use App\Models\Setting;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Link count spam protection', function (): void {
    beforeEach(function (): void {
        Setting::setValue('max_links', '3');
    });

    it('allows comments with few links', function (): void {
        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Check out https://example.com and https://test.com for more info.',
        ]);

        $response->assertStatus(201);
    });

    it('allows comments at max link limit', function (): void {
        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Links: https://a.com https://b.com https://c.com',
        ]);

        $response->assertStatus(201);
    });

    it('blocks comments with too many links', function (): void {
        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Spam: https://a.com https://b.com https://c.com https://d.com',
        ]);

        $response->assertStatus(422)
            ->assertJson(['error' => 'Too many links in comment.']);
    });

    it('counts both http and https links', function (): void {
        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Links: http://a.com https://b.com http://c.com https://d.com',
        ]);

        $response->assertStatus(422);
    });

    it('allows comments without links', function (): void {
        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'This is a comment without any links at all.',
        ]);

        $response->assertStatus(201);
    });
});
