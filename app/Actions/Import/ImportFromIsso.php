<?php

declare(strict_types=1);

namespace App\Actions\Import;

use App\Models\Comment;
use App\Models\ImportMapping;
use App\Models\Thread;
use App\Support\Markdown;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ImportFromIsso
{
    use AsAction;

    protected array $threadMappings = [];

    protected array $commentMappings = [];

    protected int $importedComments = 0;

    protected int $importedThreads = 0;

    /**
     * @return array{threads: int, comments: int}
     */
    public function handle(string $issoDbPath): array
    {
        if (! file_exists($issoDbPath)) {
            throw new \InvalidArgumentException("Isso database not found: {$issoDbPath}");
        }

        $isso = new \PDO("sqlite:{$issoDbPath}");
        $isso->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        DB::transaction(function () use ($isso): void {
            $this->importThreads($isso);
            $this->importComments($isso);
        });

        return [
            'threads' => $this->importedThreads,
            'comments' => $this->importedComments,
        ];
    }

    protected function importThreads(\PDO $isso): void
    {
        $stmt = $isso->query('SELECT id, uri, title FROM threads');
        $threads = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($threads as $issoThread) {
            // Check if already imported
            $mapping = ImportMapping::where('source', ImportMapping::SOURCE_ISSO)
                ->where('target_type', ImportMapping::TARGET_THREAD)
                ->where('source_id', $issoThread['id'])
                ->first();

            if ($mapping) {
                $this->threadMappings[$issoThread['id']] = $mapping->target_id;

                continue;
            }

            // Create or find thread
            $thread = Thread::firstOrCreate(
                ['uri' => $issoThread['uri']],
                ['title' => $issoThread['title']]
            );

            // Store mapping
            ImportMapping::createMapping(
                ImportMapping::SOURCE_ISSO,
                (string) $issoThread['id'],
                ImportMapping::TARGET_THREAD,
                $thread->id
            );

            $this->threadMappings[$issoThread['id']] = $thread->id;
            $this->importedThreads++;
        }
    }

    protected function importComments(\PDO $isso): void
    {
        // First pass: import all comments without parent references
        $stmt = $isso->query('
            SELECT id, tid, parent, created, modified, mode, remote_addr,
                   text, author, email, website, likes, notification
            FROM comments
            ORDER BY created ASC
        ');
        $comments = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // First import comments without parents, then with parents
        $pending = [];

        foreach ($comments as $issoComment) {
            if ($issoComment['parent'] === null || $issoComment['parent'] === 0) {
                $this->importComment($issoComment);
            } else {
                $pending[] = $issoComment;
            }
        }

        // Import replies
        foreach ($pending as $issoComment) {
            $this->importComment($issoComment);
        }
    }

    protected function importComment(array $issoComment): void
    {
        // Check if already imported
        $mapping = ImportMapping::where('source', ImportMapping::SOURCE_ISSO)
            ->where('target_type', ImportMapping::TARGET_COMMENT)
            ->where('source_id', $issoComment['id'])
            ->first();

        if ($mapping) {
            $this->commentMappings[$issoComment['id']] = $mapping->target_id;

            return;
        }

        $threadId = $this->threadMappings[$issoComment['tid']] ?? null;
        if (! $threadId) {
            return;
        }

        // Map parent ID
        $parentId = null;
        if ($issoComment['parent'] !== null && $issoComment['parent'] !== 0) {
            $parentId = $this->commentMappings[$issoComment['parent']] ?? null;
        }

        // Determine status from isso mode:
        // 1 = accepted, 2 = pending, 4 = deleted (soft delete)
        $status = match ($issoComment['mode']) {
            1 => Comment::STATUS_APPROVED,
            2 => Comment::STATUS_PENDING,
            4 => Comment::STATUS_DELETED,
            default => Comment::STATUS_APPROVED,
        };

        // Convert isso text (markdown) to HTML
        $bodyHtml = Markdown::toHtml($issoComment['text'] ?? '');

        $comment = Comment::create([
            'thread_id' => $threadId,
            'parent_id' => $parentId,
            'author' => $issoComment['author'],
            'email' => $issoComment['email'],
            'website' => $issoComment['website'],
            'body_markdown' => $issoComment['text'] ?? '',
            'body_html' => $bodyHtml,
            'status' => $status,
            'upvotes' => (int) ($issoComment['likes'] ?? 0),
            'notify_replies' => (bool) ($issoComment['notification'] ?? false),
            'remote_addr' => $issoComment['remote_addr'],
            'created_at' => date('Y-m-d H:i:s', (int) $issoComment['created']),
            'updated_at' => date('Y-m-d H:i:s', (int) ($issoComment['modified'] ?? $issoComment['created'])),
        ]);

        // Handle soft-deleted comments
        if ($status === Comment::STATUS_DELETED) {
            $comment->delete();
        }

        // Store mapping
        ImportMapping::createMapping(
            ImportMapping::SOURCE_ISSO,
            (string) $issoComment['id'],
            ImportMapping::TARGET_COMMENT,
            $comment->id
        );

        $this->commentMappings[$issoComment['id']] = $comment->id;
        $this->importedComments++;
    }
}
