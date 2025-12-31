<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\Comment;
use App\Models\Thread;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class GetDashboardStats
{
    use AsAction;

    /**
     * Get dashboard statistics.
     *
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return [
            'total_comments' => Comment::count(),
            'pending_comments' => Comment::where('status', Comment::STATUS_PENDING)->count(),
            'approved_comments' => Comment::where('status', Comment::STATUS_APPROVED)->count(),
            'spam_comments' => Comment::where('status', Comment::STATUS_SPAM)->count(),
            'total_threads' => Thread::count(),
            'recent_comments' => $this->getRecentComments(),
            'comments_per_month' => $this->getCommentsPerMonth(),
        ];
    }

    /**
     * Get recent comments for the dashboard.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRecentComments(): array
    {
        return Comment::with('thread')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn (Comment $comment) => [
                'id' => $comment->id,
                'author' => $comment->author,
                'body_excerpt' => \App\Support\Markdown::toPlainText($comment->body_markdown, 100),
                'status' => $comment->status,
                'thread_uri' => $comment->thread->uri,
                'thread_title' => $comment->thread->title,
                'created_at' => $comment->created_at->toIso8601String(),
            ])
            ->toArray();
    }

    /**
     * Get comments count per month for the last 12 months.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getCommentsPerMonth(): array
    {
        $startDate = now()->subMonths(11)->startOfMonth();

        // Database-specific month grouping
        $driver = DB::getDriverName();

        $monthColumn = match ($driver) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "TO_CHAR(created_at, 'YYYY-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        $results = Comment::where('created_at', '>=', $startDate)
            ->selectRaw("$monthColumn as month, count(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        // Fill in missing months with 0
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i)->format('Y-m');
            $data[] = [
                'date' => $month,
                'count' => $results[$month] ?? 0,
            ];
        }

        return $data;
    }
}
