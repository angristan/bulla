<?php

declare(strict_types=1);

namespace App\Actions\Import;

use App\Models\Comment;
use App\Models\Thread;
use Lorisleiva\Actions\Concerns\AsAction;

class ExportToJson
{
    use AsAction;

    /**
     * Export all data to JSON format.
     *
     * @return array{version: string, exported_at: string, threads: array<mixed>}
     */
    public function handle(): array
    {
        $threads = Thread::with(['comments' => function ($query): void {
            $query->withTrashed()->orderBy('created_at', 'asc');
        }])->get();

        return [
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'threads' => $threads->map(function (Thread $thread) {
                return [
                    'uri' => $thread->uri,
                    'title' => $thread->title,
                    'url' => $thread->url,
                    'created_at' => $thread->created_at->toIso8601String(),
                    'comments' => $thread->comments->map(function (Comment $comment) {
                        return [
                            'id' => $comment->id,
                            'parent_id' => $comment->parent_id,
                            'author' => $comment->author,
                            'email' => $comment->email,
                            'website' => $comment->website,
                            'body_markdown' => $comment->body_markdown,
                            'status' => $comment->status,
                            'email_verified' => $comment->email_verified,
                            'upvotes' => $comment->upvotes,
                            'notify_replies' => $comment->notify_replies,
                            'is_admin' => $comment->is_admin,
                            'created_at' => $comment->created_at->toIso8601String(),
                            'updated_at' => $comment->updated_at->toIso8601String(),
                            'deleted_at' => $comment->deleted_at?->toIso8601String(),
                        ];
                    })->toArray(),
                ];
            })->toArray(),
        ];
    }
}
