<?php

declare(strict_types=1);

namespace App\Support;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\DisallowedRawHtml\DisallowedRawHtmlExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\MarkdownConverter;

class Markdown
{
    private static ?MarkdownConverter $converter = null;

    /**
     * Convert markdown to HTML.
     */
    public static function toHtml(string $markdown): string
    {
        return (string) self::getConverter()->convert($markdown);
    }

    /**
     * Get or create the CommonMark converter.
     */
    private static function getConverter(): MarkdownConverter
    {
        if (self::$converter === null) {
            $environment = new Environment([
                'html_input' => 'escape',
                'allow_unsafe_links' => false,
                'max_nesting_level' => 10,
            ]);

            $environment->addExtension(new CommonMarkCoreExtension);
            $environment->addExtension(new AutolinkExtension);
            $environment->addExtension(new StrikethroughExtension);
            $environment->addExtension(new DisallowedRawHtmlExtension);

            self::$converter = new MarkdownConverter($environment);
        }

        return self::$converter;
    }

    /**
     * Strip markdown to plain text (for previews/excerpts).
     */
    public static function toPlainText(string $markdown, int $maxLength = 200): string
    {
        // Convert to HTML first, then strip tags
        $html = self::toHtml($markdown);
        $text = strip_tags($html);

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // Truncate if needed
        if (mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength - 3).'...';
        }

        return $text;
    }
}
