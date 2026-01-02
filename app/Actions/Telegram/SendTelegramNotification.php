<?php

declare(strict_types=1);

namespace App\Actions\Telegram;

use App\Models\Comment;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class SendTelegramNotification
{
    use AsAction;

    private const TELEGRAM_API_BASE = 'https://api.telegram.org/bot';

    /**
     * Send a Telegram notification for a comment.
     * Returns the Telegram message ID if successful, null otherwise.
     */
    public function handle(Comment $comment): ?int
    {
        if (! $this->isEnabled()) {
            return null;
        }

        // Don't notify for admin comments
        if ($comment->is_admin) {
            return null;
        }

        $botToken = Setting::getValue('telegram_bot_token');
        $chatId = Setting::getValue('telegram_chat_id');

        if (! $botToken || ! $chatId) {
            return null;
        }

        $message = $this->formatMessage($comment);

        try {
            $response = Http::post(self::TELEGRAM_API_BASE.$botToken.'/sendMessage', [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            if ($response->successful() && $response->json('ok')) {
                $messageId = $response->json('result.message_id');

                // Store the message ID on the comment for webhook lookups
                $comment->update(['telegram_message_id' => $messageId]);

                return $messageId;
            }

            Log::warning('Telegram API error', [
                'response' => $response->json(),
                'comment_id' => $comment->id,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Telegram notification failed', [
                'error' => $e->getMessage(),
                'comment_id' => $comment->id,
            ]);

            return null;
        }
    }

    /**
     * Send upvote notification.
     */
    public function handleUpvote(Comment $comment, int $newUpvoteCount): ?int
    {
        if (! $this->isEnabled()) {
            return null;
        }

        if (Setting::getValue('telegram_notify_upvotes', 'false') !== 'true') {
            return null;
        }

        $botToken = Setting::getValue('telegram_bot_token');
        $chatId = Setting::getValue('telegram_chat_id');

        if (! $botToken || ! $chatId) {
            return null;
        }

        $message = $this->formatUpvoteMessage($comment, $newUpvoteCount);

        try {
            $response = Http::post(self::TELEGRAM_API_BASE.$botToken.'/sendMessage', [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            if ($response->successful() && $response->json('ok')) {
                return $response->json('result.message_id');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Telegram upvote notification failed', [
                'error' => $e->getMessage(),
                'comment_id' => $comment->id,
            ]);

            return null;
        }
    }

    /**
     * Send a test message.
     *
     * @return array{success: bool, message: string}
     */
    public function sendTest(): array
    {
        $botToken = Setting::getValue('telegram_bot_token');
        $chatId = Setting::getValue('telegram_chat_id');

        if (! $botToken) {
            return ['success' => false, 'message' => 'Bot token not configured'];
        }

        if (! $chatId) {
            return ['success' => false, 'message' => 'Chat ID not configured'];
        }

        $siteName = Setting::getValue('site_name', 'Bulla');
        $message = "Test notification from <b>{$siteName}</b>\n\nIf you see this, Telegram integration is working!";

        try {
            $response = Http::post(self::TELEGRAM_API_BASE.$botToken.'/sendMessage', [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful() && $response->json('ok')) {
                return ['success' => true, 'message' => 'Test message sent to Telegram'];
            }

            return [
                'success' => false,
                'message' => $response->json('description', 'Unknown error'),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function isEnabled(): bool
    {
        return Setting::getValue('enable_telegram', 'false') === 'true';
    }

    private function formatMessage(Comment $comment): string
    {
        $comment->loadMissing(['thread', 'parent']);

        $statusBadge = $comment->isPending() ? ' [PENDING]' : '';
        $author = htmlspecialchars($comment->author ?? 'Anonymous', ENT_QUOTES, 'UTF-8');
        $email = $comment->email ? htmlspecialchars($comment->email, ENT_QUOTES, 'UTF-8') : null;
        $body = Str::limit(strip_tags($comment->body_html), 200);
        $body = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');

        $baseUrl = $comment->thread->url
            ?? rtrim(Setting::getValue('site_url', ''), '/').$comment->thread->uri;
        $threadUrl = "{$baseUrl}#comment-{$comment->id}";
        $threadTitle = htmlspecialchars($comment->thread->title ?? $comment->thread->uri, ENT_QUOTES, 'UTF-8');

        // Quote parent comment if this is a reply
        $parentQuote = '';
        if ($comment->parent) {
            $parentAuthor = htmlspecialchars($comment->parent->author ?? 'Anonymous', ENT_QUOTES, 'UTF-8');
            $parentDate = $comment->parent->created_at->format('M j, Y H:i');
            $parentBody = Str::limit(strip_tags($comment->parent->body_html), 100);
            $parentBody = htmlspecialchars($parentBody, ENT_QUOTES, 'UTF-8');
            $parentQuote = "\n\n<b>↩️ In reply to {$parentAuthor}</b> ({$parentDate}):\n<blockquote>{$parentBody}</blockquote>";
        }

        $reactions = $comment->isPending()
            ? '👌 Approve • 💩 Delete • 👍 Upvote'
            : '💩 Delete • 👍 Upvote';

        if (Setting::getValue('enable_downvotes', 'false') === 'true') {
            $reactions .= ' • 👎 Downvote';
        }

        $authorLine = $email ? "{$author} ({$email})" : $author;
        $adminUrl = url("/admin/comments/{$comment->id}");

        return "<b>New Comment{$statusBadge}</b>\n\n"
            ."<b>From:</b> {$authorLine}\n"
            ."<b>Thread:</b> {$threadTitle}\n\n"
            ."<blockquote>{$body}</blockquote>"
            .$parentQuote
            ."\n\n<a href=\"{$threadUrl}\">View on site</a> • <a href=\"{$adminUrl}\">View in admin</a>\n"
            ."{$reactions}\n"
            .'<i>Reply to this message to post a reply comment as admin</i>';
    }

    private function formatUpvoteMessage(Comment $comment, int $count): string
    {
        $comment->loadMissing('thread');

        $author = htmlspecialchars($comment->author ?? 'Anonymous', ENT_QUOTES, 'UTF-8');
        $body = Str::limit(strip_tags($comment->body_html), 100);
        $body = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');

        $baseUrl = $comment->thread->url
            ?? rtrim(Setting::getValue('site_url', ''), '/').$comment->thread->uri;
        $threadUrl = "{$baseUrl}#comment-{$comment->id}";
        $threadTitle = htmlspecialchars($comment->thread->title ?? $comment->thread->uri, ENT_QUOTES, 'UTF-8');
        $adminUrl = url("/admin/comments/{$comment->id}");

        return "👍 <b>Upvote Received</b>\n\n"
            ."Comment by {$author} now has <b>{$count}</b> upvote(s)\n"
            ."<b>Thread:</b> {$threadTitle}\n\n"
            ."<i>{$body}</i>\n\n"
            ."<a href=\"{$threadUrl}\">View on site</a> • <a href=\"{$adminUrl}\">View in admin</a>";
    }
}
