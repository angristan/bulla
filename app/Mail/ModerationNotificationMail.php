<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ModerationNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $moderationToken;

    public function __construct(
        public Comment $comment,
    ) {
        $this->moderationToken = Str::random(64);
    }

    public function envelope(): Envelope
    {
        $siteName = \App\Models\Setting::getValue('site_name', 'Comments');

        return new Envelope(
            subject: "New comment pending moderation - {$siteName}",
        );
    }

    public function content(): Content
    {
        $approveUrl = url("/moderate/{$this->comment->id}/approve/{$this->moderationToken}");
        $deleteUrl = url("/moderate/{$this->comment->id}/delete/{$this->moderationToken}");
        $adminUrl = url("/admin/comments/{$this->comment->id}");
        $baseUrl = $this->comment->thread->url
            ?? rtrim(\App\Models\Setting::getValue('site_url', ''), '/').$this->comment->thread->uri;
        $threadUrl = "{$baseUrl}#comment-{$this->comment->id}";

        return new Content(
            markdown: 'emails.moderation-notification',
            with: [
                'comment' => $this->comment,
                'approveUrl' => $approveUrl,
                'deleteUrl' => $deleteUrl,
                'adminUrl' => $adminUrl,
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
