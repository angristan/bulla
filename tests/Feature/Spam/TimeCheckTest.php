<?php

declare(strict_types=1);

use App\Actions\Spam\GenerateTimestamp;
use App\Actions\Telegram\SendTelegramNotification;
use App\Models\Comment;
use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Queue;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Time check spam protection', function (): void {
    beforeEach(function (): void {
        Setting::setValue('spam_min_time_seconds', '3');
        Setting::setValue('moderation_mode', 'none');
        Queue::fake();
    });

    it('approves comments with a valid timestamp after the minimum time', function (): void {
        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Test comment',
            'timestamp' => Crypt::encryptString((string) (time() - 5)),
        ]);

        $response->assertStatus(201)->assertJsonPath('status', 'approved');
        expect(Comment::first()->status)->toBe(Comment::STATUS_APPROVED);
        SendTelegramNotification::assertPushed();
    });

    it('accepts a token from a page left open for several hours', function (): void {
        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Slow writer',
            'timestamp' => Crypt::encryptString((string) (time() - 6 * 3600)),
        ]);

        $response->assertStatus(201);
        expect(Comment::first()->status)->toBe(Comment::STATUS_APPROVED);
    });

    it('blocks comments submitted too fast', function (): void {
        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Bot comment',
            'timestamp' => Crypt::encryptString((string) (time() - 1)),
        ]);

        $response->assertStatus(422)
            ->assertJson(['error' => 'Please wait a moment before submitting.']);
    });

    it('silently stores comments as spam when the timestamp is unusable', function (array $extra): void {
        $response = $this->postJson('/api/threads/test/comments', [
            'body' => 'Direct API post',
            ...$extra,
        ]);

        // The client sees a normal pending comment, not a rejection.
        $response->assertStatus(201)->assertJsonPath('status', 'pending');
        expect(Comment::first()->status)->toBe(Comment::STATUS_SPAM);
        SendTelegramNotification::assertNotPushed();
    })->with([
        // Closures defer Crypt until the application has booted.
        'missing' => fn (): array => [],
        'forged' => fn (): array => ['timestamp' => 'invalid_encrypted_value'],
        'expired' => fn (): array => ['timestamp' => Crypt::encryptString((string) (time() - GenerateTimestamp::MAX_AGE_SECONDS - 60))],
        'from the future' => fn (): array => ['timestamp' => Crypt::encryptString((string) (time() + 3600))],
    ]);

    it('does not flag admin comments without a timestamp', function (): void {
        $response = $this->withSession(['admin_authenticated' => true])
            ->postJson('/api/threads/test/comments', ['body' => 'Admin reply']);

        $response->assertStatus(201)->assertJsonPath('status', 'approved');
        expect(Comment::first()->status)->toBe(Comment::STATUS_APPROVED);
    });

    it('hides spam comments from the public thread', function (): void {
        $this->postJson('/api/threads/test/comments', ['body' => 'Direct API post']);

        $this->getJson('/api/threads/test/comments')
            ->assertOk()
            ->assertDontSee('Direct API post');
    });

    it('generates valid timestamp via action', function (): void {
        $timestamp = GenerateTimestamp::run();

        $decoded = GenerateTimestamp::validate($timestamp);
        expect($decoded)->not->toBeNull();
        expect($decoded)->toBeLessThanOrEqual(time());
        expect($decoded)->toBeGreaterThan(time() - 10);
    });
});
