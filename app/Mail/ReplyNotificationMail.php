<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Comment;
use App\Models\NotificationSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReplyNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Comment $reply,
        public Comment $parentComment,
        public NotificationSubscription $subscription,
    ) {}

    public function envelope(): Envelope
    {
        $siteName = \App\Models\Setting::getValue('site_name', 'Comments');

        return new Envelope(
            subject: "New reply to your comment - {$siteName}",
        );
    }

    public function content(): Content
    {
        $unsubscribeUrl = url("/unsubscribe/{$this->subscription->unsubscribe_token}");
        $baseUrl = $this->reply->thread->url
            ?? rtrim(\App\Models\Setting::getValue('site_url', ''), '/').$this->reply->thread->uri;
        $threadUrl = "{$baseUrl}#comment-{$this->reply->id}";

        return new Content(
            markdown: 'emails.reply-notification',
            with: [
                'reply' => $this->reply,
                'parentComment' => $this->parentComment,
                'unsubscribeUrl' => $unsubscribeUrl,
                'threadUrl' => $threadUrl,
                'siteName' => \App\Models\Setting::getValue('site_name', 'Comments'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
