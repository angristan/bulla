<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Comment;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class NewCommentNotificationMail extends Mailable
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
        $siteName = Setting::getValue('site_name', 'Comments');
        $status = $this->comment->isPending() ? ' (pending)' : '';

        return new Envelope(
            subject: "New comment{$status} - {$siteName}",
        );
    }

    public function content(): Content
    {
        $deleteUrl = url("/moderate/{$this->comment->id}/delete/{$this->moderationToken}");
        $adminUrl = url("/admin/comments/{$this->comment->id}");
        $baseUrl = $this->comment->thread->url
            ?? rtrim(Setting::getValue('site_url', ''), '/').$this->comment->thread->uri;
        $threadUrl = "{$baseUrl}#comment-{$this->comment->id}";

        $with = [
            'comment' => $this->comment,
            'deleteUrl' => $deleteUrl,
            'adminUrl' => $adminUrl,
            'threadUrl' => $threadUrl,
            'siteName' => Setting::getValue('site_name', 'Comments'),
            'isPending' => $this->comment->isPending(),
        ];

        // Add approve URL only for pending comments
        if ($this->comment->isPending()) {
            $with['approveUrl'] = url("/moderate/{$this->comment->id}/approve/{$this->moderationToken}");
        }

        return new Content(
            markdown: 'emails.new-comment-notification',
            with: $with,
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
