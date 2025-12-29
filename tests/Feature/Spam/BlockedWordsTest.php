<?php

declare(strict_types=1);

use App\Models\Setting;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Blocked words spam protection', function (): void {
    it('allows comments without blocked words', function (): void {
        Setting::setValue('blocked_words', "viagra\ncasino\npayday loan");

        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'This is a normal comment about coding.',
        ]);

        $response->assertStatus(201);
    });

    it('blocks comments with blocked words', function (): void {
        Setting::setValue('blocked_words', "viagra\ncasino\npayday loan");

        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Buy cheap viagra now!',
        ]);

        $response->assertStatus(422)
            ->assertJson(['error' => 'Your comment contains blocked content.']);
    });

    it('is case insensitive', function (): void {
        Setting::setValue('blocked_words', 'spam');

        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'This is SPAM content',
        ]);

        $response->assertStatus(422);
    });

    it('allows comments when no blocked words are set', function (): void {
        Setting::setValue('blocked_words', '');

        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'viagra casino payday loan',
        ]);

        $response->assertStatus(201);
    });

    it('blocks multi-word phrases', function (): void {
        Setting::setValue('blocked_words', "payday loan\nget rich quick");

        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Get a payday loan now!',
        ]);

        $response->assertStatus(422);
    });
});
