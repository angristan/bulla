<?php

declare(strict_types=1);

namespace Tests\Feature\Feed;

use App\Models\Comment;
use App\Models\Setting;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtomFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_recent_feed_returns_atom_xml(): void
    {
        Setting::setValue('site_name', 'Test Site');

        $thread = Thread::factory()->create(['uri' => '/test-post']);
        Comment::factory()->create([
            'thread_id' => $thread->id,
            'author' => 'John Doe',
            'body_html' => '<p>Test comment</p>',
            'status' => 'approved',
        ]);

        $response = $this->get('/feed/recent.atom');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/atom+xml; charset=UTF-8');

        $content = $response->getContent();
        $this->assertStringContainsString('<feed xmlns="http://www.w3.org/2005/Atom">', $content);
        $this->assertStringContainsString('Test Site', $content);
        $this->assertStringContainsString('John Doe', $content);
    }

    public function test_recent_feed_only_includes_approved_comments(): void
    {
        $thread = Thread::factory()->create();
        Comment::factory()->create([
            'thread_id' => $thread->id,
            'author' => 'Approved User',
            'status' => 'approved',
        ]);
        Comment::factory()->create([
            'thread_id' => $thread->id,
            'author' => 'Pending User',
            'status' => 'pending',
        ]);

        $response = $this->get('/feed/recent.atom');

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertStringContainsString('Approved User', $content);
        $this->assertStringNotContainsString('Pending User', $content);
    }

    public function test_thread_feed_returns_comments_for_specific_thread(): void
    {
        $thread1 = Thread::factory()->create(['uri' => '/blog/post-1']);
        $thread2 = Thread::factory()->create(['uri' => '/blog/post-2']);

        Comment::factory()->create([
            'thread_id' => $thread1->id,
            'author' => 'Thread1 Author',
            'status' => 'approved',
        ]);
        Comment::factory()->create([
            'thread_id' => $thread2->id,
            'author' => 'Thread2 Author',
            'status' => 'approved',
        ]);

        $response = $this->get('/feed/blog/post-1.atom');

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertStringContainsString('Thread1 Author', $content);
        $this->assertStringNotContainsString('Thread2 Author', $content);
    }

    public function test_thread_feed_handles_nonexistent_thread(): void
    {
        $response = $this->get('/feed/nonexistent.atom');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/atom+xml; charset=UTF-8');
    }

    public function test_feed_includes_entry_metadata(): void
    {
        $thread = Thread::factory()->create([
            'uri' => '/test',
            'url' => 'https://example.com/test',
        ]);
        Comment::factory()->create([
            'thread_id' => $thread->id,
            'author' => 'Test Author',
            'website' => 'https://author.example.com',
            'body_html' => '<p>Test content</p>',
            'status' => 'approved',
        ]);

        $response = $this->get('/feed/recent.atom');

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertStringContainsString('<entry>', $content);
        $this->assertStringContainsString('Test Author', $content);
        $this->assertStringContainsString('https://author.example.com', $content);
        $this->assertStringContainsString('Test content', $content);
    }

    public function test_feed_limits_to_50_entries(): void
    {
        $thread = Thread::factory()->create();

        // Create 60 comments
        Comment::factory()->count(60)->create([
            'thread_id' => $thread->id,
            'status' => 'approved',
        ]);

        $response = $this->get('/feed/recent.atom');

        $response->assertStatus(200);
        // Count entries
        $content = $response->getContent();
        $entryCount = substr_count($content, '<entry>');
        $this->assertEquals(50, $entryCount);
    }
}
