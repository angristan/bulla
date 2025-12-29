<?php

declare(strict_types=1);

namespace App\Actions\Import;

use App\Models\Comment;
use App\Models\ImportMapping;
use App\Models\Thread;
use App\Support\Markdown;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ImportFromWordPress
{
    use AsAction;

    protected int $importedThreads = 0;

    protected int $importedComments = 0;

    /** @var array<int, int> */
    protected array $threadMappings = [];

    /** @var array<int, int> */
    protected array $commentMappings = [];

    /**
     * Import from WordPress WXR export file.
     *
     * @return array{threads: int, comments: int}
     */
    public function handle(string $wxrPath): array
    {
        if (! file_exists($wxrPath)) {
            throw new \InvalidArgumentException("WordPress export file not found: {$wxrPath}");
        }

        $xml = simplexml_load_file($wxrPath);
        if ($xml === false) {
            throw new \InvalidArgumentException('Invalid XML file');
        }

        // Register WordPress namespaces
        $namespaces = $xml->getNamespaces(true);
        $wp = $namespaces['wp'] ?? 'http://wordpress.org/export/1.2/';

        DB::transaction(function () use ($xml, $wp): void {
            foreach ($xml->channel->item as $item) {
                $this->importPost($item, $wp);
            }
        });

        return [
            'threads' => $this->importedThreads,
            'comments' => $this->importedComments,
        ];
    }

    protected function importPost(\SimpleXMLElement $item, string $wpNamespace): void
    {
        $wp = $item->children($wpNamespace);

        // Get post URL and create thread
        $postUrl = (string) $item->link;
        $postTitle = (string) $item->title;
        $postId = (string) $wp->post_id;

        // Parse URL to get URI path
        $parsedUrl = parse_url($postUrl);
        $uri = $parsedUrl['path'] ?? '/';

        // Check if already imported
        $existingMapping = ImportMapping::where('source', ImportMapping::SOURCE_WORDPRESS)
            ->where('target_type', ImportMapping::TARGET_THREAD)
            ->where('source_id', $postId)
            ->first();

        if ($existingMapping) {
            $this->threadMappings[$postId] = $existingMapping->target_id;
        } else {
            // Create or find thread
            $thread = Thread::firstOrCreate(
                ['uri' => $uri],
                [
                    'title' => $postTitle,
                    'url' => $postUrl,
                ]
            );

            ImportMapping::createMapping(
                ImportMapping::SOURCE_WORDPRESS,
                $postId,
                ImportMapping::TARGET_THREAD,
                $thread->id
            );

            $this->threadMappings[$postId] = $thread->id;
            $this->importedThreads++;
        }

        // Import comments for this post
        foreach ($wp->comment as $comment) {
            $this->importComment($comment, $postId, $wpNamespace);
        }
    }

    protected function importComment(\SimpleXMLElement $comment, string $postId, string $wpNamespace): void
    {
        $commentId = (string) $comment->comment_id;
        $parentId = (string) $comment->comment_parent;

        // Check if already imported
        $existingMapping = ImportMapping::where('source', ImportMapping::SOURCE_WORDPRESS)
            ->where('target_type', ImportMapping::TARGET_COMMENT)
            ->where('source_id', $commentId)
            ->first();

        if ($existingMapping) {
            $this->commentMappings[$commentId] = $existingMapping->target_id;

            return;
        }

        $threadId = $this->threadMappings[$postId] ?? null;
        if (! $threadId) {
            return;
        }

        // Map parent ID
        $mappedParentId = null;
        if ($parentId !== '' && $parentId !== '0') {
            $mappedParentId = $this->commentMappings[$parentId] ?? null;
        }

        // Determine status from WordPress comment_approved:
        // 1 = approved, 0 = pending, spam = spam, trash = deleted
        $wpStatus = (string) $comment->comment_approved;
        $status = match ($wpStatus) {
            '1' => Comment::STATUS_APPROVED,
            '0' => Comment::STATUS_PENDING,
            'spam' => Comment::STATUS_SPAM,
            'trash' => Comment::STATUS_DELETED,
            default => Comment::STATUS_APPROVED,
        };

        $content = (string) $comment->comment_content;
        $bodyHtml = Markdown::toHtml($content);

        $newComment = Comment::create([
            'thread_id' => $threadId,
            'parent_id' => $mappedParentId,
            'author' => (string) $comment->comment_author ?: null,
            'email' => (string) $comment->comment_author_email ?: null,
            'website' => (string) $comment->comment_author_url ?: null,
            'body_markdown' => $content,
            'body_html' => $bodyHtml,
            'status' => $status,
            'remote_addr' => (string) $comment->comment_author_IP ?: null,
            'created_at' => (string) $comment->comment_date,
            'updated_at' => (string) $comment->comment_date,
        ]);

        // Handle deleted comments
        if ($status === Comment::STATUS_DELETED) {
            $newComment->delete();
        }

        ImportMapping::createMapping(
            ImportMapping::SOURCE_WORDPRESS,
            $commentId,
            ImportMapping::TARGET_COMMENT,
            $newComment->id
        );

        $this->commentMappings[$commentId] = $newComment->id;
        $this->importedComments++;
    }
}
