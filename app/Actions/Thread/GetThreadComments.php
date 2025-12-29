<?php

declare(strict_types=1);

namespace App\Actions\Thread;

use App\Models\Comment;
use App\Models\Thread;
use App\Support\Gravatar;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class GetThreadComments
{
    use AsAction;

    /**
     * Get all approved comments for a thread, nested by parent.
     *
     * @return array<string, mixed>
     */
    public function handle(Thread $thread, bool $includeHidden = false): array
    {
        $query = $thread->comments()
            ->with('replies')
            ->whereNull('parent_id')
            ->orderBy('created_at', 'asc');

        if (! $includeHidden) {
            $query->where('status', Comment::STATUS_APPROVED);
        }

        $comments = $query->get();

        return [
            'thread' => [
                'id' => $thread->id,
                'uri' => $thread->uri,
                'title' => $thread->title,
            ],
            'comments' => $this->formatComments($comments, $includeHidden),
            'total' => $thread->approvedCommentsCount(),
        ];
    }

    /**
     * Format comments for API response.
     *
     * @param  Collection<int, Comment>  $comments
     * @return array<int, array<string, mixed>>
     */
    private function formatComments(Collection $comments, bool $includeHidden): array
    {
        return $comments->map(function (Comment $comment) use ($includeHidden) {
            return $this->formatComment($comment, $includeHidden);
        })->toArray();
    }

    /**
     * Format a single comment.
     *
     * @return array<string, mixed>
     */
    private function formatComment(Comment $comment, bool $includeHidden): array
    {
        $replies = $comment->replies;

        if (! $includeHidden) {
            $replies = $replies->where('status', Comment::STATUS_APPROVED);
        }

        return [
            'id' => $comment->id,
            'parent_id' => $comment->parent_id,
            'author' => $comment->author,
            'email_verified' => $comment->email_verified,
            'is_admin' => $comment->is_admin,
            'avatar' => Gravatar::url($comment->email),
            'website' => $comment->website,
            'body_html' => $comment->body_html,
            'upvotes' => $comment->upvotes,
            'created_at' => $comment->created_at->toIso8601String(),
            'replies' => $replies
                ->sortBy('created_at')
                ->values()
                ->map(fn (Comment $reply) => $this->formatComment($reply, $includeHidden))
                ->toArray(),
        ];
    }
}
