<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Thread;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('POST /api/counts', function (): void {
    it('returns counts for multiple URIs', function (): void {
        $thread1 = Thread::create(['uri' => '/page1']);
        $thread2 = Thread::create(['uri' => '/page2']);

        Comment::create([
            'thread_id' => $thread1->id,
            'body_markdown' => 'Test',
            'body_html' => '<p>Test</p>',
            'status' => 'approved',
        ]);
        Comment::create([
            'thread_id' => $thread1->id,
            'body_markdown' => 'Test 2',
            'body_html' => '<p>Test 2</p>',
            'status' => 'approved',
        ]);
        Comment::create([
            'thread_id' => $thread2->id,
            'body_markdown' => 'Test',
            'body_html' => '<p>Test</p>',
            'status' => 'approved',
        ]);

        $response = $this->postJson('/api/counts', [
            'uris' => ['/page1', '/page2', '/page3'],
        ]);

        $response->assertOk()
            ->assertJson([
                '/page1' => 2,
                '/page2' => 1,
                '/page3' => 0,
            ]);
    });

    it('only counts approved comments', function (): void {
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

        $response = $this->postJson('/api/counts', [
            'uris' => ['/test'],
        ]);

        $response->assertOk()
            ->assertJson(['/test' => 1]);
    });

    it('validates request', function (): void {
        $response = $this->postJson('/api/counts', []);

        $response->assertStatus(422);
    });

    it('normalizes URIs', function (): void {
        Thread::create(['uri' => '/test']);

        $response = $this->postJson('/api/counts', [
            'uris' => ['test', '/test/', '//test//'],
        ]);

        $response->assertOk();

        // All variations should resolve to the same count
        $data = $response->json();
        expect($data['test'])->toBe($data['/test/']);
    });
});
