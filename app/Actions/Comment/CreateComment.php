<?php

declare(strict_types=1);

namespace App\Actions\Comment;

use App\Actions\Email\SendNewCommentNotification;
use App\Actions\Email\SendReplyNotification;
use App\Actions\Telegram\SendTelegramNotification;
use App\Actions\Thread\GetOrCreateThread;
use App\Models\Comment;
use App\Models\Setting;
use App\Support\IpAnonymizer;
use App\Support\Markdown;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateComment
{
    use AsAction;

    /**
     * @param  array{
     *     uri: string,
     *     parent_id?: int|null,
     *     author?: string|null,
     *     email?: string|null,
     *     github_id?: string|null,
     *     github_username?: string|null,
     *     website?: string|null,
     *     body: string,
     *     notify_replies?: bool,
     *     is_admin?: bool,
     *     title?: string|null,
     *     url?: string|null,
     * }  $data
     * @param  string|null  $ip  Raw IP address (will be anonymized)
     * @param  string|null  $userAgent  User agent string
     */
    public function handle(array $data, ?string $ip = null, ?string $userAgent = null): Comment
    {
        // Get or create the thread
        $thread = GetOrCreateThread::run(
            $data['uri'],
            $data['title'] ?? null,
            $data['url'] ?? null
        );

        // Calculate depth from parent
        $parentId = $data['parent_id'] ?? null;
        $depth = 0;

        if ($parentId !== null) {
            $parentComment = Comment::find($parentId);
            if ($parentComment === null) {
                throw new \InvalidArgumentException('Parent comment not found');
            }

            $depth = $parentComment->depth + 1;
        }

        // Determine status based on moderation settings
        $status = $this->determineStatus($data);

        // Create the comment
        $comment = Comment::create([
            'thread_id' => $thread->id,
            'parent_id' => $parentId,
            'depth' => $depth,
            'author' => $data['author'] ?? null,
            'email' => $data['email'] ?? null,
            'github_id' => $data['github_id'] ?? null,
            'github_username' => $data['github_username'] ?? null,
            'website' => $this->sanitizeWebsite($data['website'] ?? null),
            'is_admin' => $data['is_admin'] ?? false,
            'body_markdown' => $data['body'],
            'body_html' => Markdown::toHtml($data['body']),
            'status' => $status,
            'notify_replies' => $data['notify_replies'] ?? false,
            'remote_addr' => IpAnonymizer::anonymize($ip),
            'user_agent' => $userAgent ? Str::limit($userAgent, 512, '') : null,
            'edit_token' => Str::random(64),
            'edit_token_expires_at' => now()->addMinutes(
                (int) Setting::getValue('edit_window_minutes', '15')
            ),
        ]);

        // Send notifications (email + telegram)
        $this->sendNotifications($comment);

        return $comment;
    }

    private function sendNotifications(Comment $comment): void
    {
        // Email notifications (only if mail is configured)
        if (config('mail.default') !== 'log') {
            // Send new comment notification to admin (skips admin comments)
            SendNewCommentNotification::run($comment);

            // Send reply notification to parent comment author
            if ($comment->parent_id) {
                SendReplyNotification::run($comment);
            }
        }

        // Telegram notification (queued)
        SendTelegramNotification::dispatch($comment);
    }

    /**
     * Determine the initial status of the comment.
     *
     * @param  array<string, mixed>  $data
     */
    private function determineStatus(array $data): string
    {
        // Admin comments are always approved
        if ($data['is_admin'] ?? false) {
            return Comment::STATUS_APPROVED;
        }

        // Check moderation mode
        $moderationMode = Setting::getValue('moderation_mode', 'none');

        return match ($moderationMode) {
            'all' => Comment::STATUS_PENDING,
            default => Comment::STATUS_APPROVED,
        };
    }

    /**
     * Sanitize website URL.
     */
    private function sanitizeWebsite(?string $website): ?string
    {
        if ($website === null || $website === '') {
            return null;
        }

        // Validate URL (must include protocol)
        if (filter_var($website, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return $website;
    }
}
