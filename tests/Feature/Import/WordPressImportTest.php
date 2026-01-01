<?php

declare(strict_types=1);

namespace Tests\Feature\Import;

use App\Actions\Import\ImportFromWordPress;
use App\Models\Comment;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WordPressImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_imports_threads_and_comments(): void
    {
        $xml = $this->createWordPressXml([
            [
                'post_id' => '1',
                'title' => 'Test Post',
                'link' => 'https://example.com/test-post',
                'comments' => [
                    [
                        'comment_id' => '1',
                        'author' => 'John Doe',
                        'author_email' => 'john@example.com',
                        'content' => 'Test comment',
                        'approved' => '1',
                        'date' => '2024-01-01 12:00:00',
                    ],
                ],
            ],
        ]);

        $path = $this->createXmlFile($xml);

        $result = ImportFromWordPress::run($path);

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
        $xml = $this->createWordPressXml([
            [
                'post_id' => '1',
                'title' => 'Test Post',
                'link' => 'https://example.com/test',
                'comments' => [
                    [
                        'comment_id' => '1',
                        'comment_parent' => '0',
                        'author' => 'Parent',
                        'content' => 'Parent comment',
                        'approved' => '1',
                        'date' => '2024-01-01 12:00:00',
                    ],
                    [
                        'comment_id' => '2',
                        'comment_parent' => '1',
                        'author' => 'Reply',
                        'content' => 'Reply comment',
                        'approved' => '1',
                        'date' => '2024-01-01 12:01:00',
                    ],
                ],
            ],
        ]);

        $path = $this->createXmlFile($xml);

        ImportFromWordPress::run($path);

        $parent = Comment::where('author', 'Parent')->first();
        $reply = Comment::where('author', 'Reply')->first();

        $this->assertNotNull($parent);
        $this->assertNotNull($reply);
        $this->assertEquals($parent->id, $reply->parent_id);
    }

    public function test_handles_different_comment_statuses(): void
    {
        $xml = $this->createWordPressXml([
            [
                'post_id' => '1',
                'title' => 'Test Post',
                'link' => 'https://example.com/test',
                'comments' => [
                    [
                        'comment_id' => '1',
                        'author' => 'Approved',
                        'content' => 'Approved comment',
                        'approved' => '1',
                        'date' => '2024-01-01 12:00:00',
                    ],
                    [
                        'comment_id' => '2',
                        'author' => 'Pending',
                        'content' => 'Pending comment',
                        'approved' => '0',
                        'date' => '2024-01-01 12:00:00',
                    ],
                    [
                        'comment_id' => '3',
                        'author' => 'Spam',
                        'content' => 'Spam comment',
                        'approved' => 'spam',
                        'date' => '2024-01-01 12:00:00',
                    ],
                    [
                        'comment_id' => '4',
                        'author' => 'Trashed',
                        'content' => 'Trashed comment',
                        'approved' => 'trash',
                        'date' => '2024-01-01 12:00:00',
                    ],
                ],
            ],
        ]);

        $path = $this->createXmlFile($xml);

        ImportFromWordPress::run($path);

        $this->assertDatabaseHas('comments', ['author' => 'Approved', 'status' => Comment::STATUS_APPROVED]);
        $this->assertDatabaseHas('comments', ['author' => 'Pending', 'status' => Comment::STATUS_PENDING]);
        $this->assertDatabaseHas('comments', ['author' => 'Spam', 'status' => Comment::STATUS_SPAM]);
        $this->assertSoftDeleted('comments', ['author' => 'Trashed']);
    }

    public function test_fails_with_missing_file(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not found');

        ImportFromWordPress::run('/nonexistent/path.xml');
    }

    public function test_fails_with_invalid_xml(): void
    {
        $path = Storage::disk('local')->path('invalid.xml');
        file_put_contents($path, 'not valid xml');

        $this->expectException(\ErrorException::class);

        ImportFromWordPress::run($path);
    }

    public function test_does_not_process_xxe_external_entities(): void
    {
        $secretFile = Storage::disk('local')->path('secret.txt');
        file_put_contents($secretFile, 'SECRET_CONTENT_12345');

        $maliciousXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE rss [
  <!ENTITY xxe SYSTEM "file://{$secretFile}">
]>
<rss version="2.0"
    xmlns:wp="http://wordpress.org/export/1.2/"
    xmlns:content="http://purl.org/rss/1.0/modules/content/">
    <channel>
        <item>
            <title>&xxe;</title>
            <link>https://example.com/test</link>
            <wp:post_id>1</wp:post_id>
            <wp:comment>
                <wp:comment_id>1</wp:comment_id>
                <wp:comment_author><![CDATA[Test]]></wp:comment_author>
                <wp:comment_author_email><![CDATA[test@example.com]]></wp:comment_author_email>
                <wp:comment_content><![CDATA[&xxe;]]></wp:comment_content>
                <wp:comment_approved>1</wp:comment_approved>
                <wp:comment_date>2024-01-01 12:00:00</wp:comment_date>
            </wp:comment>
        </item>
    </channel>
</rss>
XML;

        $path = $this->createXmlFile($maliciousXml);

        ImportFromWordPress::run($path);

        $thread = Thread::first();
        $comment = Comment::first();

        $this->assertNotNull($thread);
        $this->assertStringNotContainsString('SECRET_CONTENT_12345', $thread->title ?? '');
        $this->assertStringNotContainsString('SECRET_CONTENT_12345', $comment->body_markdown ?? '');
    }

    protected function createWordPressXml(array $posts): string
    {
        $items = '';
        foreach ($posts as $post) {
            $comments = '';
            foreach ($post['comments'] ?? [] as $comment) {
                $commentParent = $comment['comment_parent'] ?? '0';
                $authorEmail = $comment['author_email'] ?? '';
                $authorUrl = $comment['author_url'] ?? '';
                $authorIp = $comment['author_ip'] ?? '';

                $comments .= <<<XML
                <wp:comment>
                    <wp:comment_id>{$comment['comment_id']}</wp:comment_id>
                    <wp:comment_parent>{$commentParent}</wp:comment_parent>
                    <wp:comment_author><![CDATA[{$comment['author']}]]></wp:comment_author>
                    <wp:comment_author_email><![CDATA[{$authorEmail}]]></wp:comment_author_email>
                    <wp:comment_author_url><![CDATA[{$authorUrl}]]></wp:comment_author_url>
                    <wp:comment_author_IP><![CDATA[{$authorIp}]]></wp:comment_author_IP>
                    <wp:comment_content><![CDATA[{$comment['content']}]]></wp:comment_content>
                    <wp:comment_approved>{$comment['approved']}</wp:comment_approved>
                    <wp:comment_date>{$comment['date']}</wp:comment_date>
                </wp:comment>
XML;
            }

            $items .= <<<XML
            <item>
                <title>{$post['title']}</title>
                <link>{$post['link']}</link>
                <wp:post_id>{$post['post_id']}</wp:post_id>
                {$comments}
            </item>
XML;
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"
    xmlns:wp="http://wordpress.org/export/1.2/"
    xmlns:content="http://purl.org/rss/1.0/modules/content/">
    <channel>
        {$items}
    </channel>
</rss>
XML;
    }

    protected function createXmlFile(string $xml): string
    {
        $path = Storage::disk('local')->path('test-import.xml');
        file_put_contents($path, $xml);

        return $path;
    }
}
