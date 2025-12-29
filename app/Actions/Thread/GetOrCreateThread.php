<?php

declare(strict_types=1);

namespace App\Actions\Thread;

use App\Models\Thread;
use Lorisleiva\Actions\Concerns\AsAction;

class GetOrCreateThread
{
    use AsAction;

    public function handle(string $uri, ?string $title = null, ?string $url = null): Thread
    {
        // Normalize URI (remove trailing slashes, leading slashes)
        $uri = '/'.trim($uri, '/');

        return Thread::firstOrCreate(
            ['uri' => $uri],
            [
                'title' => $title,
                'url' => $url,
            ]
        );
    }
}
