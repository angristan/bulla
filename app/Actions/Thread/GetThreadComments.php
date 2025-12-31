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

    public const SORT_OLDEST = 'oldest';

    public const SORT_NEWEST = 'newest';

    public const SORT_POPULAR = 'popular';

    public const VALID_SORTS = [self::SORT_OLDEST, self::SORT_NEWEST, self::SORT_POPULAR];

    /**
     * Get all approved comments for a thread, nested by parent.
     *
     * @return array<string, mixed>
     */
    public function handle(Thread $thread, bool $includeHidden = false, string $sort = self::SORT_OLDEST): array
    {
        $query = $thread->comments()
            ->with('replies')
            ->whereNull('parent_id');

        // Apply sorting
        match ($sort) {
            self::SORT_NEWEST => $query->orderBy('created_at', 'desc'),
            self::SORT_POPULAR => $query->orderByRaw('(upvotes - downvotes) DESC')->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'asc'), // oldest
        };

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
            'comments' => $this->formatComments($comments, $includeHidden, $sort),
            'total' => $includeHidden ? $thread->comments()->count() : $thread->approvedCommentsCount(),
        ];
    }

    /**
     * Format comments for API response.
     *
     * @param  Collection<int, Comment>  $comments
     * @return array<int, array<string, mixed>>
     */
    private function formatComments(Collection $comments, bool $includeHidden, string $sort): array
    {
        return $comments->map(function (Comment $comment) use ($includeHidden, $sort) {
            return $this->formatComment($comment, $includeHidden, $sort);
        })->toArray();
    }

    /**
     * Format a single comment.
     *
     * @param  Comment|null  $rootParent  The parent comment for nested replies (to show "replying to")
     * @return array<string, mixed>
     */
    private function formatComment(Comment $comment, bool $includeHidden, string $sort, ?Comment $rootParent = null): array
    {
        $replies = $comment->replies;

        if (! $includeHidden) {
            $replies = $replies->where('status', Comment::STATUS_APPROVED);
        }

        // Sort replies - always chronological for replies (oldest first for conversation flow)
        $sortedReplies = $replies->sortBy('created_at')->values();

        // Use GitHub avatar if available, otherwise Gravatar
        $avatarUrl = $comment->github_username
            ? "https://github.com/{$comment->github_username}.png"
            : ($comment->display_email
                ? Gravatar::url($comment->display_email)
                : Gravatar::urlForIp($comment->remote_addr, (string) $comment->thread_id));

        return [
            'id' => $comment->id,
            'parent_id' => $comment->parent_id,
            'parent_author' => $rootParent?->display_author,
            'depth' => $comment->depth,
            'author' => $comment->display_author,
            'is_admin' => $comment->is_admin,
            'is_github_user' => $comment->github_id !== null,
            'github_username' => $comment->github_username,
            'avatar' => $avatarUrl,
            'website' => $comment->website,
            'body_html' => $comment->body_html,
            'upvotes' => $comment->upvotes,
            'downvotes' => $comment->downvotes,
            'created_at' => $comment->created_at->toIso8601String(),
            'replies' => $sortedReplies
                ->map(fn (Comment $reply) => $this->formatComment($reply, $includeHidden, $sort, $comment))
                ->toArray(),
        ];
    }
}
