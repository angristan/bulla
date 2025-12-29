<?php

declare(strict_types=1);

namespace App\Actions\Email;

use App\Models\Comment;
use App\Models\EmailVerification;
use Lorisleiva\Actions\Concerns\AsAction;

class VerifyEmail
{
    use AsAction;

    public function handle(string $token): bool
    {
        $verification = EmailVerification::where('token', $token)->first();

        if (! $verification) {
            return false;
        }

        if ($verification->expires_at->isPast()) {
            return false;
        }

        // Mark the verification as used
        $verification->update(['verified_at' => now()]);

        // Mark all comments with this email as verified
        Comment::where('email', $verification->email)
            ->where('email_verified', false)
            ->update(['email_verified' => true]);

        return true;
    }
}
