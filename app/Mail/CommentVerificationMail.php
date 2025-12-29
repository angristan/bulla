<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Comment;
use App\Models\EmailVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommentVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Comment $comment,
        public EmailVerification $verification,
    ) {}

    public function envelope(): Envelope
    {
        $siteName = \App\Models\Setting::getValue('site_name', 'Comments');

        return new Envelope(
            subject: "Verify your email - {$siteName}",
        );
    }

    public function content(): Content
    {
        $verifyUrl = url("/verify/{$this->verification->token}");

        return new Content(
            markdown: 'emails.comment-verification',
            with: [
                'comment' => $this->comment,
                'verifyUrl' => $verifyUrl,
                'siteName' => \App\Models\Setting::getValue('site_name', 'Comments'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
