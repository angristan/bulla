<?php

declare(strict_types=1);

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Rate limit spam protection', function (): void {
    beforeEach(function (): void {
        Cache::flush();
        Setting::setValue('rate_limit_per_minute', '3');
    });

    it('allows comments under rate limit', function (): void {
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/threads/test/comments', [
                'body' => "Comment $i",
            ]);

            $response->assertStatus(201);
        }
    });

    it('blocks comments over rate limit', function (): void {
        // First 3 should succeed
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/threads/test/comments', [
                'body' => "Comment $i",
            ])->assertStatus(201);
        }

        // 4th should be blocked
        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Spam comment',
        ]);

        $response->assertStatus(422)
            ->assertJson(['error' => 'Too many comments. Please wait a minute.']);
    });

    it('tracks rate limit per IP', function (): void {
        Setting::setValue('rate_limit_per_minute', '2');

        // IP 1 posts 2 comments
        $this->postJson('/api/threads/test/comments', ['body' => 'Test 1'], ['REMOTE_ADDR' => '1.1.1.1'])->assertStatus(201);
        $this->postJson('/api/threads/test/comments', ['body' => 'Test 2'], ['REMOTE_ADDR' => '1.1.1.1'])->assertStatus(201);

        // IP 1 is blocked
        $this->postJson('/api/threads/test/comments', ['body' => 'Test 3'], ['REMOTE_ADDR' => '1.1.1.1'])->assertStatus(422);

        // IP 2 can still post
        $this->postJson('/api/threads/test/comments', ['body' => 'Test 1'], ['REMOTE_ADDR' => '2.2.2.2'])->assertStatus(201);
    });
});
