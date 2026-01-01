<?php

declare(strict_types=1);

namespace Tests\Feature\Import;

use App\Actions\Import\ImportFromDisqus;
use App\Models\Comment;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DisqusImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_imports_threads_and_comments(): void
    {
        $xml = $this->createDisqusXml(
            threads: [
                [
                    'id' => 'thread1',
                    'link' => 'https://example.com/test-post',
                    'title' => 'Test Post',
                ],
            ],
            posts: [
                [
                    'id' => 'post1',
                    'thread_id' => 'thread1',
                    'author_name' => 'John Doe',
                    'author_email' => 'john@example.com',
                    'message' => 'Test comment',
                    'created_at' => '2024-01-01T12:00:00Z',
                ],
            ]
        );

        $path = $this->createXmlFile($xml);

        $result = ImportFromDisqus::run($path);

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
        $xml = $this->createDisqusXml(
            threads: [
                [
                    'id' => 'thread1',
                    'link' => 'https://example.com/test',
                    'title' => 'Test',
                ],
            ],
            posts: [
                [
                    'id' => 'post1',
                    'thread_id' => 'thread1',
                    'author_name' => 'Parent',
                    'message' => 'Parent comment',
                    'created_at' => '2024-01-01T12:00:00Z',
                ],
                [
                    'id' => 'post2',
                    'thread_id' => 'thread1',
                    'parent_id' => 'post1',
                    'author_name' => 'Reply',
                    'message' => 'Reply comment',
                    'created_at' => '2024-01-01T12:01:00Z',
                ],
            ]
        );

        $path = $this->createXmlFile($xml);

        ImportFromDisqus::run($path);

        $parent = Comment::where('author', 'Parent')->first();
        $reply = Comment::where('author', 'Reply')->first();

        $this->assertNotNull($parent);
        $this->assertNotNull($reply);
        $this->assertEquals($parent->id, $reply->parent_id);
    }

    public function test_handles_deleted_and_spam_comments(): void
    {
        $xml = $this->createDisqusXml(
            threads: [
                [
                    'id' => 'thread1',
                    'link' => 'https://example.com/test',
                    'title' => 'Test',
                ],
            ],
            posts: [
                [
                    'id' => 'post1',
                    'thread_id' => 'thread1',
                    'author_name' => 'Normal',
                    'message' => 'Normal comment',
                    'created_at' => '2024-01-01T12:00:00Z',
                ],
                [
                    'id' => 'post2',
                    'thread_id' => 'thread1',
                    'author_name' => 'Deleted',
                    'message' => 'Deleted comment',
                    'is_deleted' => 'true',
                    'created_at' => '2024-01-01T12:00:00Z',
                ],
                [
                    'id' => 'post3',
                    'thread_id' => 'thread1',
                    'author_name' => 'Spam',
                    'message' => 'Spam comment',
                    'is_spam' => 'true',
                    'created_at' => '2024-01-01T12:00:00Z',
                ],
            ]
        );

        $path = $this->createXmlFile($xml);

        ImportFromDisqus::run($path);

        $this->assertDatabaseHas('comments', ['author' => 'Normal', 'status' => Comment::STATUS_APPROVED]);
        $this->assertDatabaseHas('comments', ['author' => 'Spam', 'status' => Comment::STATUS_SPAM]);
        $this->assertSoftDeleted('comments', ['author' => 'Deleted']);
    }

    public function test_fails_with_missing_file(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not found');

        ImportFromDisqus::run('/nonexistent/path.xml');
    }

    public function test_fails_with_invalid_xml(): void
    {
        $path = Storage::disk('local')->path('invalid.xml');
        file_put_contents($path, 'not valid xml');

        $this->expectException(\ErrorException::class);

        ImportFromDisqus::run($path);
    }

    public function test_does_not_process_xxe_external_entities(): void
    {
        $secretFile = Storage::disk('local')->path('secret.txt');
        file_put_contents($secretFile, 'SECRET_CONTENT_12345');

        $maliciousXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE disqus [
  <!ENTITY xxe SYSTEM "file://{$secretFile}">
]>
<disqus xmlns="http://disqus.com"
        xmlns:dsq="http://disqus.com/disqus-internals">
    <thread dsq:id="thread1">
        <link>https://example.com/test</link>
        <title>&xxe;</title>
    </thread>
    <post dsq:id="post1">
        <thread dsq:id="thread1"/>
        <message><![CDATA[&xxe;]]></message>
        <createdAt>2024-01-01T12:00:00Z</createdAt>
        <isDeleted>false</isDeleted>
        <isSpam>false</isSpam>
        <author>
            <name>Test</name>
            <email>test@example.com</email>
        </author>
    </post>
</disqus>
XML;

        $path = $this->createXmlFile($maliciousXml);

        ImportFromDisqus::run($path);

        $thread = Thread::first();
        $comment = Comment::first();

        $this->assertNotNull($thread);
        $this->assertStringNotContainsString('SECRET_CONTENT_12345', $thread->title ?? '');
        $this->assertStringNotContainsString('SECRET_CONTENT_12345', $comment->body_markdown ?? '');
    }

    protected function createDisqusXml(array $threads, array $posts): string
    {
        $threadXml = '';
        foreach ($threads as $thread) {
            $threadXml .= <<<XML
    <thread dsq:id="{$thread['id']}">
        <link>{$thread['link']}</link>
        <title>{$thread['title']}</title>
    </thread>
XML;
        }

        $postXml = '';
        foreach ($posts as $post) {
            $parentXml = isset($post['parent_id'])
                ? "<parent dsq:id=\"{$post['parent_id']}\"/>"
                : '';
            $isDeleted = $post['is_deleted'] ?? 'false';
            $isSpam = $post['is_spam'] ?? 'false';
            $authorEmail = $post['author_email'] ?? '';

            $postXml .= <<<XML
    <post dsq:id="{$post['id']}">
        <thread dsq:id="{$post['thread_id']}"/>
        {$parentXml}
        <message><![CDATA[{$post['message']}]]></message>
        <createdAt>{$post['created_at']}</createdAt>
        <isDeleted>{$isDeleted}</isDeleted>
        <isSpam>{$isSpam}</isSpam>
        <author>
            <name>{$post['author_name']}</name>
            <email>{$authorEmail}</email>
        </author>
    </post>
XML;
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<disqus xmlns="http://disqus.com"
        xmlns:dsq="http://disqus.com/disqus-internals">
{$threadXml}
{$postXml}
</disqus>
XML;
    }

    protected function createXmlFile(string $xml): string
    {
        $path = Storage::disk('local')->path('test-import.xml');
        file_put_contents($path, $xml);

        return $path;
    }
}
