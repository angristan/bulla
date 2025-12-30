<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Thread\GetCommentCounts;
use App\Actions\Thread\GetThreadComments;
use App\Http\Controllers\Controller;
use App\Models\Thread;
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
        $normalizedUri = '/'.trim($uri, '/');

        // Find thread (check trailing slash version first for backward compatibility)
        $thread = Thread::where('uri', $normalizedUri.'/')
            ->orWhere('uri', $normalizedUri)
            ->first();

        if (! $thread) {
            // Return empty response for non-existent thread (don't create it)
            return response()->json([
                'thread' => [
                    'id' => null,
                    'uri' => $normalizedUri,
                    'title' => null,
                ],
                'comments' => [],
                'total' => 0,
            ]);
        }

        // Include hidden comments (pending/spam) when admin is authenticated
        $includeHidden = $request->hasSession()
            && (bool) $request->session()->get('admin_authenticated', false)
            && $request->query('guest') !== '1';

        $data = GetThreadComments::run($thread, $includeHidden);

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
