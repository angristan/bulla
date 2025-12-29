<?php

declare(strict_types=1);

namespace App\Actions\Email;

use App\Mail\CommentVerificationMail;
use App\Models\Comment;
use App\Models\EmailVerification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class SendVerificationEmail
{
    use AsAction;

    public function handle(Comment $comment): ?EmailVerification
    {
        if (! $comment->email) {
            return null;
        }

        // Check if already verified
        if ($comment->email_verified) {
            return null;
        }

        // Create or get existing verification
        $verification = EmailVerification::firstOrCreate(
            ['email' => $comment->email],
            [
                'token' => Str::random(64),
                'expires_at' => now()->addDays(7),
            ]
        );

        // If token expired, regenerate
        if ($verification->expires_at->isPast()) {
            $verification->update([
                'token' => Str::random(64),
                'expires_at' => now()->addDays(7),
            ]);
        }

        Mail::to($comment->email)->send(new CommentVerificationMail($comment, $verification));

        return $verification;
    }
}
