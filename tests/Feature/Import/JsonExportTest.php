<?php

declare(strict_types=1);

namespace Tests\Feature\Import;

use App\Actions\Import\ExportToJson;
use App\Models\Comment;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JsonExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_exports_threads_and_comments(): void
    {
        $thread = Thread::factory()->create([
            'uri' => '/test-post',
            'title' => 'Test Post',
        ]);

        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'author' => 'John Doe',
            'email' => 'john@example.com',
            'body_markdown' => 'Test comment',
            'status' => 'approved',
        ]);

        $result = ExportToJson::run();

        $this->assertEquals('1.0', $result['version']);
        $this->assertNotEmpty($result['exported_at']);
        $this->assertCount(1, $result['threads']);

        $exportedThread = $result['threads'][0];
        $this->assertEquals('/test-post', $exportedThread['uri']);
        $this->assertEquals('Test Post', $exportedThread['title']);
        $this->assertCount(1, $exportedThread['comments']);

        $exportedComment = $exportedThread['comments'][0];
        $this->assertEquals('John Doe', $exportedComment['author']);
        $this->assertEquals('john@example.com', $exportedComment['email']);
    }

    public function test_exports_deleted_comments(): void
    {
        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
        ]);
        $comment->delete();

        $result = ExportToJson::run();

        $this->assertCount(1, $result['threads'][0]['comments']);
        $this->assertNotNull($result['threads'][0]['comments'][0]['deleted_at']);
    }

    public function test_exports_nested_comments(): void
    {
        $thread = Thread::factory()->create();
        $parent = Comment::factory()->create([
            'thread_id' => $thread->id,
            'author' => 'Parent',
        ]);
        $reply = Comment::factory()->create([
            'thread_id' => $thread->id,
            'parent_id' => $parent->id,
            'author' => 'Reply',
        ]);

        $result = ExportToJson::run();

        $comments = $result['threads'][0]['comments'];
        $this->assertCount(2, $comments);

        $parentExport = collect($comments)->firstWhere('author', 'Parent');
        $replyExport = collect($comments)->firstWhere('author', 'Reply');

        $this->assertNull($parentExport['parent_id']);
        $this->assertEquals($parent->id, $replyExport['parent_id']);
    }
}
