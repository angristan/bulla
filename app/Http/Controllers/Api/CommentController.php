<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Comment\CreateComment;
use App\Actions\Comment\DeleteComment;
use App\Actions\Comment\DownvoteComment;
use App\Actions\Comment\PreviewMarkdown;
use App\Actions\Comment\UpdateComment;
use App\Actions\Comment\UpvoteComment;
use App\Actions\Spam\CheckSpam;
use App\Actions\Spam\GenerateTimestamp;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Support\Gravatar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Create a new comment.
     */
    public function store(Request $request, string $uri): JsonResponse
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
            'author' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'required_if:notify_replies,true'],
            'website' => ['nullable', 'string', 'max:512'],
            'body' => ['required', 'string', 'min:1', 'max:65535'],
            'notify_replies' => ['nullable', 'boolean'],
            'title' => ['nullable', 'string', 'max:512'],
            'url' => ['nullable', 'string', 'max:1024'],
            'honeypot' => ['nullable', 'string'],
            'timestamp' => ['nullable', 'string'],
        ]);

        // Decode timestamp
        $timestamp = null;
        if (isset($validated['timestamp'])) {
            $timestamp = GenerateTimestamp::validate($validated['timestamp']);
        }

        // Check for spam
        $spamError = CheckSpam::run(
            [
                'honeypot' => $validated['honeypot'] ?? null,
                'timestamp' => $timestamp,
                'body' => $validated['body'],
            ],
            $request->ip()
        );

        if ($spamError !== null) {
            return response()->json(['error' => $spamError], 422);
        }

        // Check if user is authenticated as admin via session
        $isAdmin = (bool) $request->session()->get('admin_authenticated', false);

        try {
            $comment = CreateComment::run(
                [
                    'uri' => urldecode($uri),
                    'parent_id' => $validated['parent_id'] ?? null,
                    'author' => $validated['author'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'website' => $validated['website'] ?? null,
                    'body' => $validated['body'],
                    'notify_replies' => $validated['notify_replies'] ?? false,
                    'title' => $validated['title'] ?? null,
                    'url' => $validated['url'] ?? null,
                    'is_admin' => $isAdmin,
                ],
                $request->ip(),
                $request->userAgent()
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'id' => $comment->id,
            'author' => $comment->display_author,
            'is_admin' => $comment->is_admin,
            'avatar' => $comment->display_email
                ? Gravatar::url($comment->display_email)
                : Gravatar::urlForIp($comment->remote_addr, (string) $comment->thread_id),
            'website' => $comment->website,
            'body_html' => $comment->body_html,
            'status' => $comment->status,
            'upvotes' => $comment->upvotes,
            'downvotes' => $comment->downvotes,
            'created_at' => $comment->created_at->toIso8601String(),
            'edit_token' => $comment->edit_token,
            'edit_token_expires_at' => $comment->edit_token_expires_at?->toIso8601String(),
        ], 201);
    }

    /**
     * Get a single comment.
     */
    public function show(Comment $comment): JsonResponse
    {
        return response()->json([
            'id' => $comment->id,
            'author' => $comment->display_author,
            'is_admin' => $comment->is_admin,
            'avatar' => $comment->display_email
                ? Gravatar::url($comment->display_email)
                : Gravatar::urlForIp($comment->remote_addr, (string) $comment->thread_id),
            'website' => $comment->website,
            'body_html' => $comment->body_html,
            'body_markdown' => $comment->body_markdown,
            'status' => $comment->status,
            'upvotes' => $comment->upvotes,
            'downvotes' => $comment->downvotes,
            'created_at' => $comment->created_at->toIso8601String(),
        ]);
    }

    /**
     * Update a comment.
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['nullable', 'string', 'min:1', 'max:65535'],
            'author' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:512'],
            'edit_token' => ['required', 'string'],
        ]);

        $updated = UpdateComment::run(
            $comment,
            [
                'body' => $validated['body'] ?? null,
                'author' => $validated['author'] ?? null,
                'website' => $validated['website'] ?? null,
            ],
            $validated['edit_token']
        );

        if ($updated === null) {
            return response()->json(['error' => 'Invalid or expired edit token.'], 403);
        }

        return response()->json([
            'id' => $updated->id,
            'author' => $updated->display_author,
            'website' => $updated->website,
            'body_html' => $updated->body_html,
            'body_markdown' => $updated->body_markdown,
        ]);
    }

    /**
     * Delete a comment.
     */
    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        $validated = $request->validate([
            'edit_token' => ['required', 'string'],
        ]);

        $deleted = DeleteComment::run($comment, $validated['edit_token']);

        if (! $deleted) {
            return response()->json(['error' => 'Invalid or expired edit token.'], 403);
        }

        return response()->json(['deleted' => true]);
    }

    /**
     * Upvote a comment.
     */
    public function upvote(Request $request, Comment $comment): JsonResponse
    {
        $newCount = UpvoteComment::run(
            $comment,
            $request->ip(),
            $request->userAgent()
        );

        if ($newCount === null) {
            return response()->json(['error' => 'Already voted.'], 409);
        }

        return response()->json(['upvotes' => $newCount]);
    }

    /**
     * Downvote a comment.
     */
    public function downvote(Request $request, Comment $comment): JsonResponse
    {
        $newCount = DownvoteComment::run(
            $comment,
            $request->ip(),
            $request->userAgent()
        );

        if ($newCount === null) {
            return response()->json(['error' => 'Already voted.'], 409);
        }

        return response()->json(['downvotes' => $newCount]);
    }

    /**
     * Preview markdown.
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:65535'],
        ]);

        $html = PreviewMarkdown::run($validated['body']);

        return response()->json(['html' => $html]);
    }
}
