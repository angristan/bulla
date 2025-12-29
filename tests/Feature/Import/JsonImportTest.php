<?php

declare(strict_types=1);

namespace Tests\Feature\Import;

use App\Actions\Import\ImportFromJson;
use App\Models\Comment;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JsonImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_imports_threads_and_comments(): void
    {
        $json = [
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'threads' => [
                [
                    'uri' => '/test-post',
                    'title' => 'Test Post',
                    'url' => 'https://example.com/test-post',
                    'comments' => [
                        [
                            'id' => 1,
                            'author' => 'John Doe',
                            'email' => 'john@example.com',
                            'body_markdown' => 'Test comment',
                            'status' => 'approved',
                        ],
                    ],
                ],
            ],
        ];

        $path = $this->createJsonFile($json);

        $result = ImportFromJson::run($path);

        $this->assertEquals(1, $result['threads']);
        $this->assertEquals(1, $result['comments']);

        $this->assertDatabaseHas('threads', [
            'uri' => '/test-post',
            'title' => 'Test Post',
        ]);

        $this->assertDatabaseHas('comments', [
            'author' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_imports_nested_comments(): void
    {
        $json = [
            'version' => '1.0',
            'threads' => [
                [
                    'uri' => '/test',
                    'comments' => [
                        [
                            'id' => 1,
                            'parent_id' => null,
                            'author' => 'Parent',
                            'body_markdown' => 'Parent comment',
                            'status' => 'approved',
                        ],
                        [
                            'id' => 2,
                            'parent_id' => 1,
                            'author' => 'Reply',
                            'body_markdown' => 'Reply comment',
                            'status' => 'approved',
                        ],
                    ],
                ],
            ],
        ];

        $path = $this->createJsonFile($json);

        ImportFromJson::run($path);

        $parent = Comment::where('author', 'Parent')->first();
        $reply = Comment::where('author', 'Reply')->first();

        $this->assertNotNull($parent);
        $this->assertNotNull($reply);
        $this->assertEquals($parent->id, $reply->parent_id);
    }

    public function test_handles_deleted_comments(): void
    {
        $json = [
            'version' => '1.0',
            'threads' => [
                [
                    'uri' => '/test',
                    'comments' => [
                        [
                            'id' => 1,
                            'author' => 'Deleted',
                            'body_markdown' => 'Deleted comment',
                            'status' => 'deleted',
                            'deleted_at' => now()->toIso8601String(),
                        ],
                    ],
                ],
            ],
        ];

        $path = $this->createJsonFile($json);

        ImportFromJson::run($path);

        $this->assertSoftDeleted('comments', ['author' => 'Deleted']);
    }

    public function test_converts_markdown_to_html(): void
    {
        $json = [
            'version' => '1.0',
            'threads' => [
                [
                    'uri' => '/test',
                    'comments' => [
                        [
                            'id' => 1,
                            'author' => 'Test',
                            'body_markdown' => '**bold** text',
                            'status' => 'approved',
                        ],
                    ],
                ],
            ],
        ];

        $path = $this->createJsonFile($json);

        ImportFromJson::run($path);

        $comment = Comment::where('author', 'Test')->first();
        $this->assertStringContainsString('<strong>bold</strong>', $comment->body_html);
    }

    public function test_fails_with_invalid_json(): void
    {
        $path = Storage::disk('local')->path('invalid.json');
        file_put_contents($path, 'not valid json');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON');

        ImportFromJson::run($path);
    }

    public function test_fails_with_missing_file(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not found');

        ImportFromJson::run('/nonexistent/path.json');
    }

    public function test_does_not_duplicate_existing_threads(): void
    {
        Thread::factory()->create(['uri' => '/existing']);

        $json = [
            'version' => '1.0',
            'threads' => [
                [
                    'uri' => '/existing',
                    'comments' => [
                        ['id' => 1, 'author' => 'Test', 'body_markdown' => 'Test', 'status' => 'approved'],
                    ],
                ],
            ],
        ];

        $path = $this->createJsonFile($json);

        $result = ImportFromJson::run($path);

        $this->assertEquals(0, $result['threads']); // Thread already existed
        $this->assertEquals(1, $result['comments']);
        $this->assertCount(1, Thread::all());
    }

    protected function createJsonFile(array $data): string
    {
        $path = Storage::disk('local')->path('test-import.json');
        file_put_contents($path, json_encode($data));

        return $path;
    }
}
