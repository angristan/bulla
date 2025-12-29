<?php

declare(strict_types=1);

use App\Actions\Spam\GenerateTimestamp;
use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Time check spam protection', function (): void {
    beforeEach(function (): void {
        Setting::setValue('spam_min_time_seconds', '3');
    });

    it('allows comments without timestamp', function (): void {
        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Test comment',
        ]);

        $response->assertStatus(201);
    });

    it('allows comments with valid timestamp after min time', function (): void {
        // Generate timestamp 5 seconds ago
        $timestamp = Crypt::encryptString((string) (time() - 5));

        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Test comment',
            'timestamp' => $timestamp,
        ]);

        $response->assertStatus(201);
    });

    it('blocks comments submitted too fast', function (): void {
        // Generate timestamp 1 second ago (too fast)
        $timestamp = Crypt::encryptString((string) (time() - 1));

        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Bot comment',
            'timestamp' => $timestamp,
        ]);

        $response->assertStatus(422)
            ->assertJson(['error' => 'Please wait a moment before submitting.']);
    });

    it('ignores invalid timestamp', function (): void {
        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Test comment',
            'timestamp' => 'invalid_encrypted_value',
        ]);

        // Invalid timestamps are ignored (treated as no timestamp)
        $response->assertStatus(201);
    });

    it('ignores very old timestamps', function (): void {
        // Generate timestamp 2 hours ago (too old, might be replayed)
        $timestamp = Crypt::encryptString((string) (time() - 7200));

        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Test comment',
            'timestamp' => $timestamp,
        ]);

        // Old timestamps are ignored for safety
        $response->assertStatus(201);
    });

    it('generates valid timestamp via action', function (): void {
        $timestamp = GenerateTimestamp::run();

        expect($timestamp)->toBeString();
        expect(strlen($timestamp))->toBeGreaterThan(0);

        // Should be valid
        $decoded = GenerateTimestamp::validate($timestamp);
        expect($decoded)->not->toBeNull();
        expect($decoded)->toBeLessThanOrEqual(time());
        expect($decoded)->toBeGreaterThan(time() - 10);
    });
});
