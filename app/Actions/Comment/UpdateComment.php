<?php

declare(strict_types=1);

namespace App\Actions\Comment;

use App\Models\Comment;
use App\Support\Markdown;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateComment
{
    use AsAction;

    /**
     * @param  array{
     *     body?: string,
     *     author?: string|null,
     *     website?: string|null,
     * }  $data
     */
    public function handle(Comment $comment, array $data, string $editToken): ?Comment
    {
        // Verify edit token
        if (! $comment->canEdit($editToken)) {
            return null;
        }

        $updates = [];

        if (isset($data['body'])) {
            $updates['body_markdown'] = $data['body'];
            $updates['body_html'] = Markdown::toHtml($data['body']);
        }

        if (array_key_exists('author', $data)) {
            $updates['author'] = $data['author'];
        }

        if (array_key_exists('website', $data)) {
            $updates['website'] = $this->sanitizeWebsite($data['website']);
        }

        if (! empty($updates)) {
            $comment->update($updates);
        }

        return $comment->fresh();
    }

    /**
     * Sanitize website URL.
     */
    private function sanitizeWebsite(?string $website): ?string
    {
        if ($website === null || $website === '') {
            return null;
        }

        // Add https:// if no protocol
        if (! str_starts_with($website, 'http://') && ! str_starts_with($website, 'https://')) {
            $website = 'https://'.$website;
        }

        // Validate URL
        if (filter_var($website, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return $website;
    }
}
