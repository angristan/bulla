<?php

declare(strict_types=1);

namespace App\Actions\Comment;

use App\Support\Markdown;
use Lorisleiva\Actions\Concerns\AsAction;

class PreviewMarkdown
{
    use AsAction;

    public function handle(string $markdown): string
    {
        return Markdown::toHtml($markdown);
    }
}
