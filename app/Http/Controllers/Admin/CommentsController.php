<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ListComments;
use App\Actions\Comment\ApproveComment;
use App\Actions\Comment\DeleteComment;
use App\Actions\Comment\MarkAsSpam;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Support\Gravatar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommentsController extends Controller
{
    /**
     * List comments.
     */
    public function index(Request $request): Response
    {
        $filters = [
            'status' => $request->get('status', 'all'),
            'search' => $request->get('search'),
            'thread_id' => $request->get('thread_id'),
            'per_page' => (int) $request->get('per_page', 20),
            'sort_by' => $request->get('sort_by', 'created_at'),
            'sort_dir' => $request->get('sort_dir', 'desc'),
        ];

        $comments = ListComments::run($filters);

        return Inertia::render('Comments/Index', [
            'comments' => $comments,
            'filters' => $filters,
        ]);
    }

    /**
     * Show single comment.
     */
    public function show(Comment $comment): Response
    {
        $comment->load(['thread', 'parent', 'replies']);

        return Inertia::render('Comments/Show', [
            'comment' => [
                'id' => $comment->id,
                'author' => $comment->display_author,
                'email' => $comment->email,
                'website' => $comment->website,
                'avatar' => $comment->display_email
                    ? Gravatar::url($comment->display_email)
                    : Gravatar::urlForIp($comment->remote_addr, (string) $comment->thread_id),
                'body_markdown' => $comment->body_markdown,
                'body_html' => $comment->body_html,
                'status' => $comment->status,
                'is_admin' => $comment->is_admin,
                'upvotes' => $comment->upvotes,
                'remote_addr' => $comment->remote_addr,
                'user_agent' => $comment->user_agent,
                'created_at' => $comment->created_at->toIso8601String(),
                'thread' => [
                    'id' => $comment->thread->id,
                    'uri' => $comment->thread->uri,
                    'title' => $comment->thread->title,
                    'url' => $comment->thread->url
                        ?? rtrim(\App\Models\Setting::getValue('site_url', ''), '/').$comment->thread->uri,
                ],
                'parent' => $comment->parent ? [
                    'id' => $comment->parent->id,
                    'author' => $comment->parent->display_author,
                ] : null,
                'replies_count' => $comment->replies->count(),
            ],
        ]);
    }

    /**
     * Approve a comment.
     */
    public function approve(Comment $comment): RedirectResponse|JsonResponse
    {
        ApproveComment::run($comment);

        if (request()->expectsJson()) {
            return response()->json(['status' => 'approved']);
        }

        return back()->with('success', 'Comment approved.');
    }

    /**
     * Mark comment as spam.
     */
    public function spam(Comment $comment): RedirectResponse|JsonResponse
    {
        MarkAsSpam::run($comment);

        if (request()->expectsJson()) {
            return response()->json(['status' => 'spam']);
        }

        return back()->with('success', 'Comment marked as spam.');
    }

    /**
     * Delete a comment (admin).
     */
    public function destroy(Comment $comment): RedirectResponse|JsonResponse
    {
        DeleteComment::make()->asAdmin($comment);

        if (request()->expectsJson()) {
            return response()->json(['deleted' => true]);
        }

        return redirect()->route('admin.comments.index')
            ->with('success', 'Comment deleted.');
    }
}
