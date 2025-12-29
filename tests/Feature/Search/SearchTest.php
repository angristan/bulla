<?php

declare(strict_types=1);

namespace Tests\Feature\Search;

use App\Models\Comment;
use App\Models\Thread;
use App\Search\SearchManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_searches_comments_by_body(): void
    {
        $thread = Thread::factory()->create();
        Comment::factory()->create([
            'thread_id' => $thread->id,
            'body_markdown' => 'This is a unique test phrase',
        ]);
        Comment::factory()->create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Something completely different',
        ]);

        $searchManager = new SearchManager;
        $driver = $searchManager->driver();

        $results = $driver->search(Comment::query(), 'unique test')->get();

        $this->assertCount(1, $results);
        $this->assertStringContainsString('unique test phrase', $results->first()->body_markdown);
    }

    public function test_searches_comments_by_author(): void
    {
        $thread = Thread::factory()->create();
        Comment::factory()->create([
            'thread_id' => $thread->id,
            'author' => 'John Doe',
        ]);
        Comment::factory()->create([
            'thread_id' => $thread->id,
            'author' => 'Jane Smith',
        ]);

        $searchManager = new SearchManager;
        $driver = $searchManager->driver();

        $results = $driver->search(Comment::query(), 'John')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('John Doe', $results->first()->author);
    }

    public function test_searches_comments_by_email(): void
    {
        $thread = Thread::factory()->create();
        Comment::factory()->create([
            'thread_id' => $thread->id,
            'email' => 'john@example.com',
        ]);
        Comment::factory()->create([
            'thread_id' => $thread->id,
            'email' => 'jane@example.org',
        ]);

        $searchManager = new SearchManager;
        $driver = $searchManager->driver();

        $results = $driver->search(Comment::query(), 'john@example')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('john@example.com', $results->first()->email);
    }

    public function test_search_is_case_insensitive(): void
    {
        $thread = Thread::factory()->create();
        Comment::factory()->create([
            'thread_id' => $thread->id,
            'body_markdown' => 'UPPERCASE content',
        ]);

        $searchManager = new SearchManager;
        $driver = $searchManager->driver();

        $results = $driver->search(Comment::query(), 'uppercase')->get();

        $this->assertCount(1, $results);
    }

    public function test_search_returns_empty_for_no_matches(): void
    {
        $thread = Thread::factory()->create();
        Comment::factory()->create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Some content',
        ]);

        $searchManager = new SearchManager;
        $driver = $searchManager->driver();

        $results = $driver->search(Comment::query(), 'nonexistent phrase xyz')->get();

        $this->assertCount(0, $results);
    }

    public function test_search_handles_special_characters(): void
    {
        $thread = Thread::factory()->create();
        Comment::factory()->create([
            'thread_id' => $thread->id,
            'body_markdown' => 'Content with 100% special chars',
        ]);

        $searchManager = new SearchManager;
        $driver = $searchManager->driver();

        // Should not throw exception
        $results = $driver->search(Comment::query(), '100%')->get();

        $this->assertNotNull($results);
    }
}
