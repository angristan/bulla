<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Thread\GetCommentCounts;
use App\Actions\Thread\GetOrCreateThread;
use App\Actions\Thread\GetThreadComments;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThreadController extends Controller
{
    /**
     * Get comments for a thread.
     */
    public function comments(Request $request, string $uri): JsonResponse
    {
        $uri = urldecode($uri);
        $thread = GetOrCreateThread::run($uri);
        $data = GetThreadComments::run($thread);

        return response()->json($data);
    }

    /**
     * Get comment counts for multiple URIs.
     */
    public function counts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uris' => ['required', 'array'],
            'uris.*' => ['required', 'string'],
        ]);

        $counts = GetCommentCounts::run($validated['uris']);

        return response()->json($counts);
    }
}
