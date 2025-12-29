<?php

declare(strict_types=1);

namespace App\Actions\Email;

use App\Actions\Comment\ApproveComment;
use App\Actions\Comment\DeleteComment;
use App\Models\Comment;
use Lorisleiva\Actions\Concerns\AsAction;

class ModerateViaEmail
{
    use AsAction;

    public function approve(Comment $comment, string $token): bool
    {
        if (! $comment->canModerate($token)) {
            return false;
        }

        ApproveComment::run($comment);

        // Clear the token after use
        $comment->update(['moderation_token' => null]);

        return true;
    }

    public function delete(Comment $comment, string $token): bool
    {
        if (! $comment->canModerate($token)) {
            return false;
        }

        // Use asAdmin to ensure soft delete
        DeleteComment::make()->asAdmin($comment);

        // Clear the token after use
        $comment->update(['moderation_token' => null]);

        return true;
    }
}
