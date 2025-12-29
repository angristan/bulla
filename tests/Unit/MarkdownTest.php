<?php

declare(strict_types=1);

use App\Support\Markdown;

describe('Markdown', function (): void {
    it('converts basic markdown to HTML', function (): void {
        $html = Markdown::toHtml('**bold** and *italic*');

        expect($html)->toContain('<strong>bold</strong>');
        expect($html)->toContain('<em>italic</em>');
    });

    it('converts links', function (): void {
        $html = Markdown::toHtml('[link](https://example.com)');

        expect($html)->toContain('<a href="https://example.com">link</a>');
    });

    it('auto-links URLs', function (): void {
        $html = Markdown::toHtml('Visit https://example.com for more');

        expect($html)->toContain('href="https://example.com"');
    });

    it('escapes HTML input', function (): void {
        $html = Markdown::toHtml('<script>alert("xss")</script>');

        expect($html)->not->toContain('<script>');
        expect($html)->toContain('&lt;script&gt;');
    });

    it('supports strikethrough', function (): void {
        $html = Markdown::toHtml('~~deleted~~');

        expect($html)->toContain('<del>deleted</del>');
    });

    it('converts to plain text', function (): void {
        $text = Markdown::toPlainText('**bold** and *italic* text');

        expect($text)->toBe('bold and italic text');
    });

    it('truncates plain text to max length', function (): void {
        $longText = str_repeat('word ', 100);
        $text = Markdown::toPlainText($longText, 50);

        expect(strlen($text))->toBeLessThanOrEqual(50);
        expect($text)->toEndWith('...');
    });

    it('handles code blocks', function (): void {
        $html = Markdown::toHtml("```php\n\$foo = 'bar';\n```");

        expect($html)->toContain('<code');
        expect($html)->toContain('<pre>');
    });

    it('handles inline code', function (): void {
        $html = Markdown::toHtml('Use `console.log()` for debugging');

        expect($html)->toContain('<code>console.log()</code>');
    });
});
