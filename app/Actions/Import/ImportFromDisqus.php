<?php

declare(strict_types=1);

namespace App\Actions\Import;

use App\Models\Comment;
use App\Models\ImportMapping;
use App\Models\Thread;
use App\Support\Markdown;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ImportFromDisqus
{
    use AsAction;

    protected int $importedThreads = 0;

    protected int $importedComments = 0;

    /** @var array<string, int> */
    protected array $threadMappings = [];

    /** @var array<string, int> */
    protected array $commentMappings = [];

    /**
     * Import from Disqus XML export file.
     *
     * @return array{threads: int, comments: int}
     */
    public function handle(string $disqusPath): array
    {
        if (! file_exists($disqusPath)) {
            throw new \InvalidArgumentException("Disqus export file not found: {$disqusPath}");
        }

        $xml = simplexml_load_file($disqusPath, 'SimpleXMLElement', LIBXML_NONET);
        if ($xml === false) {
            throw new \InvalidArgumentException('Invalid XML file');
        }

        // Register Disqus namespace
        $namespaces = $xml->getNamespaces(true);
        $dsq = $namespaces['dsq'] ?? 'http://disqus.com';

        DB::transaction(function () use ($xml, $dsq): void {
            // First pass: import all threads
            foreach ($xml->thread as $thread) {
                $this->importThread($thread, $dsq);
            }

            // Second pass: import comments without parents
            foreach ($xml->post as $post) {
                $parent = $post->parent;
                if (! $parent || empty((string) $parent->attributes($dsq)->id)) {
                    $this->importComment($post, $dsq);
                }
            }

            // Third pass: import replies
            foreach ($xml->post as $post) {
                $parent = $post->parent;
                if ($parent && ! empty((string) $parent->attributes($dsq)->id)) {
                    $this->importComment($post, $dsq);
                }
            }
        });

        return [
            'threads' => $this->importedThreads,
            'comments' => $this->importedComments,
        ];
    }

    protected function importThread(\SimpleXMLElement $thread, string $dsqNamespace): void
    {
        $dsqAttrs = $thread->attributes($dsqNamespace);
        $threadId = (string) $dsqAttrs->id;

        // Check if already imported
        $existingMapping = ImportMapping::where('source', ImportMapping::SOURCE_DISQUS)
            ->where('target_type', ImportMapping::TARGET_THREAD)
            ->where('source_id', $threadId)
            ->first();

        if ($existingMapping) {
            $this->threadMappings[$threadId] = $existingMapping->target_id;

            return;
        }

        $link = (string) $thread->link;
        $title = (string) $thread->title;

        // Parse URL to get URI path
        $parsedUrl = parse_url($link);
        $uri = $parsedUrl['path'] ?? '/';

        // Create or find thread
        $newThread = Thread::firstOrCreate(
            ['uri' => $uri],
            [
                'title' => $title,
                'url' => $link,
            ]
        );

        ImportMapping::createMapping(
            ImportMapping::SOURCE_DISQUS,
            $threadId,
            ImportMapping::TARGET_THREAD,
            $newThread->id
        );

        $this->threadMappings[$threadId] = $newThread->id;
        $this->importedThreads++;
    }

    protected function importComment(\SimpleXMLElement $post, string $dsqNamespace): void
    {
        $dsqAttrs = $post->attributes($dsqNamespace);
        $commentId = (string) $dsqAttrs->id;

        // Check if already imported
        $existingMapping = ImportMapping::where('source', ImportMapping::SOURCE_DISQUS)
            ->where('target_type', ImportMapping::TARGET_COMMENT)
            ->where('source_id', $commentId)
            ->first();

        if ($existingMapping) {
            $this->commentMappings[$commentId] = $existingMapping->target_id;

            return;
        }

        // Get thread ID
        $threadRef = $post->thread;
        $threadDsqId = (string) $threadRef->attributes($dsqNamespace)->id;
        $threadId = $this->threadMappings[$threadDsqId] ?? null;

        if (! $threadId) {
            return;
        }

        // Get parent ID if exists
        $parentId = null;
        $parentRef = $post->parent;
        if ($parentRef) {
            $parentDsqId = (string) $parentRef->attributes($dsqNamespace)->id;
            if ($parentDsqId) {
                $parentId = $this->commentMappings[$parentDsqId] ?? null;
            }
        }

        // Get author info
        $author = $post->author;
        $authorName = (string) $author->name;
        $authorEmail = (string) $author->email;

        // Determine status
        $isDeleted = ((string) $post->isDeleted) === 'true';
        $isSpam = ((string) $post->isSpam) === 'true';

        $status = match (true) {
            $isDeleted => Comment::STATUS_DELETED,
            $isSpam => Comment::STATUS_SPAM,
            default => Comment::STATUS_APPROVED,
        };

        // Get message content (it's in CDATA)
        $content = (string) $post->message;
        // Disqus content is HTML, convert to markdown-safe format
        $content = strip_tags($content, '<p><br><a><strong><em><code><pre>');
        $content = str_replace(['<p>', '</p>'], ['', "\n\n"], $content);
        $content = str_replace(['<br>', '<br/>'], "\n", $content);
        $bodyHtml = Markdown::toHtml($content);

        $createdAt = (string) $post->createdAt;

        $newComment = Comment::create([
            'thread_id' => $threadId,
            'parent_id' => $parentId,
            'author' => $authorName ?: null,
            'email' => $authorEmail ?: null,
            'body_markdown' => $content,
            'body_html' => $bodyHtml,
            'status' => $status,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        // Handle deleted comments
        if ($status === Comment::STATUS_DELETED) {
            $newComment->delete();
        }

        ImportMapping::createMapping(
            ImportMapping::SOURCE_DISQUS,
            $commentId,
            ImportMapping::TARGET_COMMENT,
            $newComment->id
        );

        $this->commentMappings[$commentId] = $newComment->id;
        $this->importedComments++;
    }
}
