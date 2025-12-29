<?php

declare(strict_types=1);

namespace App\Actions\Feed;

use App\Models\Comment;
use App\Models\Setting;
use App\Models\Thread;
use Lorisleiva\Actions\Concerns\AsAction;

class GenerateAtomFeed
{
    use AsAction;

    /**
     * Generate an Atom feed for a specific thread.
     */
    public function forThread(string $uri): string
    {
        $thread = Thread::where('uri', $uri)->first();

        if (! $thread) {
            return $this->emptyFeed("Comments for {$uri}");
        }

        $comments = $thread->comments()
            ->where('status', Comment::STATUS_APPROVED)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $siteName = Setting::getValue('site_name', 'Comments');
        $title = $thread->title ? "Comments on: {$thread->title}" : "Comments for {$uri}";

        return $this->buildFeed($title, $siteName, $comments, $thread->url);
    }

    /**
     * Generate a global Atom feed with recent comments.
     */
    public function recent(): string
    {
        $comments = Comment::where('status', Comment::STATUS_APPROVED)
            ->with('thread')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $siteName = Setting::getValue('site_name', 'Comments');

        return $this->buildFeed("Recent Comments - {$siteName}", $siteName, $comments);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Comment>  $comments
     */
    protected function buildFeed(string $title, string $siteName, $comments, ?string $alternateLink = null): string
    {
        $baseUrl = config('app.url', 'http://localhost');
        $feedId = $alternateLink ?? $baseUrl.'/feed/recent.atom';
        $updated = $comments->first()?->created_at?->toIso8601String() ?? now()->toIso8601String();

        $xml = new \XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');

        $xml->startElement('feed');
        $xml->writeAttribute('xmlns', 'http://www.w3.org/2005/Atom');

        $xml->writeElement('title', $title);
        $xml->writeElement('id', $feedId);
        $xml->writeElement('updated', $updated);

        $xml->startElement('author');
        $xml->writeElement('name', $siteName);
        $xml->endElement();

        $xml->startElement('link');
        $xml->writeAttribute('href', $feedId);
        $xml->writeAttribute('rel', 'self');
        $xml->writeAttribute('type', 'application/atom+xml');
        $xml->endElement();

        if ($alternateLink) {
            $xml->startElement('link');
            $xml->writeAttribute('href', $alternateLink);
            $xml->writeAttribute('rel', 'alternate');
            $xml->writeAttribute('type', 'text/html');
            $xml->endElement();
        }

        foreach ($comments as $comment) {
            $this->writeEntry($xml, $comment, $baseUrl);
        }

        $xml->endElement(); // feed
        $xml->endDocument();

        return $xml->outputMemory();
    }

    protected function emptyFeed(string $title): string
    {
        $siteName = Setting::getValue('site_name', 'Comments');

        return $this->buildFeed($title, $siteName, collect());
    }

    protected function writeEntry(\XMLWriter $xml, Comment $comment, string $baseUrl): void
    {
        $xml->startElement('entry');

        $entryId = "{$baseUrl}/comments/{$comment->id}";
        $xml->writeElement('id', $entryId);

        $title = $comment->author ?? 'Anonymous';
        $title .= " on {$comment->thread->uri}";
        $xml->writeElement('title', $title);

        $xml->writeElement('updated', $comment->created_at->toIso8601String());
        $xml->writeElement('published', $comment->created_at->toIso8601String());

        $xml->startElement('author');
        $xml->writeElement('name', $comment->author ?? 'Anonymous');
        if ($comment->website) {
            $xml->writeElement('uri', $comment->website);
        }
        $xml->endElement();

        if ($comment->thread->url) {
            $xml->startElement('link');
            $xml->writeAttribute('href', $comment->thread->url."#comment-{$comment->id}");
            $xml->writeAttribute('rel', 'alternate');
            $xml->writeAttribute('type', 'text/html');
            $xml->endElement();
        }

        $xml->startElement('content');
        $xml->writeAttribute('type', 'html');
        $xml->text($comment->body_html);
        $xml->endElement();

        $xml->endElement(); // entry
    }
}
