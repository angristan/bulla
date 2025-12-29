<?php

declare(strict_types=1);

namespace App\Actions\Comment;

use App\Models\Comment;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteComment
{
    use AsAction;

    /**
     * Delete a comment using edit token (user deletion).
     */
    public function handle(Comment $comment, string $editToken): bool
    {
        if (! $comment->canEdit($editToken)) {
            return false;
        }

        return $this->performDelete($comment);
    }

    /**
     * Admin delete (no token required).
     */
    public function asAdmin(Comment $comment): bool
    {
        return $this->performDelete($comment);
    }

    /**
     * Perform the actual deletion.
     */
    private function performDelete(Comment $comment): bool
    {
        // If comment has replies, soft delete and mark as deleted
        if ($comment->replies()->exists()) {
            $comment->update([
                'status' => Comment::STATUS_DELETED,
                'body_markdown' => '',
                'body_html' => '',
                'author' => null,
                'email' => null,
                'website' => null,
            ]);
            $comment->delete(); // Soft delete

            return true;
        }

        // No replies, can fully delete
        $comment->forceDelete();

        return true;
    }
}
