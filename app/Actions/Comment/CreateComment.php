<?php

declare(strict_types=1);

namespace App\Actions\Comment;

use App\Actions\Email\SendModerationNotification;
use App\Actions\Email\SendReplyNotification;
use App\Actions\Email\SendVerificationEmail;
use App\Actions\Thread\GetOrCreateThread;
use App\Models\Comment;
use App\Models\EmailVerification;
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

        // Check if email is pre-verified
        $emailVerified = false;
        if (isset($data['email'])) {
            $emailVerified = EmailVerification::isEmailVerified($data['email']);
        }

        // Determine status based on moderation settings
        $status = $this->determineStatus($data, $emailVerified);

        // Create the comment
        $comment = Comment::create([
            'thread_id' => $thread->id,
            'parent_id' => $data['parent_id'] ?? null,
            'author' => $data['author'] ?? null,
            'email' => $data['email'] ?? null,
            'website' => $this->sanitizeWebsite($data['website'] ?? null),
            'is_admin' => $data['is_admin'] ?? false,
            'body_markdown' => $data['body'],
            'body_html' => Markdown::toHtml($data['body']),
            'status' => $status,
            'email_verified' => $emailVerified,
            'notify_replies' => $data['notify_replies'] ?? false,
            'remote_addr' => IpAnonymizer::anonymize($ip),
            'user_agent' => $userAgent ? Str::limit($userAgent, 512, '') : null,
            'edit_token' => Str::random(64),
            'edit_token_expires_at' => now()->addMinutes(
                (int) Setting::getValue('edit_window_minutes', '15')
            ),
        ]);

        // Send emails (only if email sending is configured)
        $this->sendEmails($comment, $emailVerified);

        return $comment;
    }

    private function sendEmails(Comment $comment, bool $alreadyVerified): void
    {
        // Only send emails if SMTP is configured
        if (! Setting::getValue('smtp_host')) {
            return;
        }

        // Send verification email if email provided and not already verified
        if ($comment->email && ! $alreadyVerified) {
            SendVerificationEmail::run($comment);
        }

        // Send moderation notification if comment is pending
        if ($comment->isPending()) {
            SendModerationNotification::run($comment);
        }

        // Send reply notification to parent comment author
        if ($comment->parent_id) {
            SendReplyNotification::run($comment);
        }
    }

    /**
     * Determine the initial status of the comment.
     *
     * @param  array<string, mixed>  $data
     */
    private function determineStatus(array $data, bool $emailVerified): string
    {
        // Admin comments are always approved
        if ($data['is_admin'] ?? false) {
            return Comment::STATUS_APPROVED;
        }

        // Check moderation mode
        $moderationMode = Setting::getValue('moderation_mode', 'none');

        return match ($moderationMode) {
            'all' => Comment::STATUS_PENDING,
            'unverified' => $emailVerified ? Comment::STATUS_APPROVED : Comment::STATUS_PENDING,
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

        // Add https:// if no protocol
        if (! str_starts_with($website, 'http://') && ! str_starts_with($website, 'https://')) {
            $website = 'https://'.$website;
        }

        // Validate URL
        if (filter_var($website, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return $website;
    }
}
