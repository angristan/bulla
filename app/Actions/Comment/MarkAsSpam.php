<?php

declare(strict_types=1);

namespace App\Actions\Comment;

use App\Models\Comment;
use Lorisleiva\Actions\Concerns\AsAction;

class MarkAsSpam
{
    use AsAction;

    public function handle(Comment $comment): Comment
    {
        $comment->update(['status' => Comment::STATUS_SPAM]);

        return $comment->fresh();
    }
}
