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
        $normalizedUri = '/'.trim($uri, '/');

        // Try to find existing thread - check trailing slash version first (backward compatibility)
        $thread = Thread::where('uri', $normalizedUri.'/')
            ->orWhere('uri', $normalizedUri)
            ->first();

        if ($thread) {
            return $thread;
        }

        return Thread::create([
            'uri' => $normalizedUri,
            'title' => $title,
            'url' => $url,
        ]);
    }
}
