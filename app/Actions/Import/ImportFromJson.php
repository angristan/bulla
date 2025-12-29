<?php

declare(strict_types=1);

namespace App\Actions\Import;

use App\Models\Comment;
use App\Models\Thread;
use App\Support\Markdown;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ImportFromJson
{
    use AsAction;

    protected int $importedThreads = 0;

    protected int $importedComments = 0;

    /**
     * @return array{threads: int, comments: int}
     */
    public function handle(string $jsonPath): array
    {
        if (! file_exists($jsonPath)) {
            throw new \InvalidArgumentException("JSON file not found: {$jsonPath}");
        }

        $content = file_get_contents($jsonPath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: '.json_last_error_msg());
        }

        if (! isset($data['threads']) || ! is_array($data['threads'])) {
            throw new \InvalidArgumentException('Invalid export format: missing threads array');
        }

        DB::transaction(function () use ($data): void {
            foreach ($data['threads'] as $threadData) {
                $this->importThread($threadData);
            }
        });

        return [
            'threads' => $this->importedThreads,
            'comments' => $this->importedComments,
        ];
    }

    /**
     * @param  array<string, mixed>  $threadData
     */
    protected function importThread(array $threadData): void
    {
        // Find or create thread
        $thread = Thread::firstOrCreate(
            ['uri' => $threadData['uri']],
            [
                'title' => $threadData['title'] ?? null,
                'url' => $threadData['url'] ?? null,
            ]
        );

        if ($thread->wasRecentlyCreated) {
            $this->importedThreads++;
        }

        if (! isset($threadData['comments']) || ! is_array($threadData['comments'])) {
            return;
        }

        // Build ID mapping for parent references
        $idMapping = [];

        // First pass: import all top-level comments
        foreach ($threadData['comments'] as $commentData) {
            if (empty($commentData['parent_id'])) {
                $comment = $this->importComment($thread, $commentData, null);
                if ($comment && isset($commentData['id'])) {
                    $idMapping[$commentData['id']] = $comment->id;
                }
            }
        }

        // Second pass: import replies
        foreach ($threadData['comments'] as $commentData) {
            if (! empty($commentData['parent_id'])) {
                $parentId = $idMapping[$commentData['parent_id']] ?? null;
                $comment = $this->importComment($thread, $commentData, $parentId);
                if ($comment && isset($commentData['id'])) {
                    $idMapping[$commentData['id']] = $comment->id;
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $commentData
     */
    protected function importComment(Thread $thread, array $commentData, ?int $parentId): ?Comment
    {
        $bodyMarkdown = $commentData['body_markdown'] ?? $commentData['body'] ?? '';
        $bodyHtml = Markdown::toHtml($bodyMarkdown);

        $comment = Comment::create([
            'thread_id' => $thread->id,
            'parent_id' => $parentId,
            'author' => $commentData['author'] ?? null,
            'email' => $commentData['email'] ?? null,
            'website' => $commentData['website'] ?? null,
            'body_markdown' => $bodyMarkdown,
            'body_html' => $bodyHtml,
            'status' => $commentData['status'] ?? Comment::STATUS_APPROVED,
            'email_verified' => $commentData['email_verified'] ?? false,
            'upvotes' => $commentData['upvotes'] ?? 0,
            'notify_replies' => $commentData['notify_replies'] ?? false,
            'is_admin' => $commentData['is_admin'] ?? false,
            'created_at' => $commentData['created_at'] ?? now(),
            'updated_at' => $commentData['updated_at'] ?? now(),
        ]);

        // Handle soft-deleted comments
        if (! empty($commentData['deleted_at'])) {
            $comment->delete();
        }

        $this->importedComments++;

        return $comment;
    }
}
