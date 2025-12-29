<?php

declare(strict_types=1);

describe('POST /api/comments/preview', function (): void {
    it('returns HTML for markdown', function (): void {
        $response = $this->postJson('/api/comments/preview', [
            'body' => '**bold** and *italic*',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['html']);

        $html = $response->json('html');
        expect($html)->toContain('<strong>bold</strong>');
        expect($html)->toContain('<em>italic</em>');
    });

    it('validates body is required', function (): void {
        $response = $this->postJson('/api/comments/preview', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    });

    it('escapes HTML in markdown', function (): void {
        $response = $this->postJson('/api/comments/preview', [
            'body' => '<script>alert("xss")</script>',
        ]);

        $response->assertOk();

        $html = $response->json('html');
        expect($html)->not->toContain('<script>');
    });

    it('handles code blocks', function (): void {
        $response = $this->postJson('/api/comments/preview', [
            'body' => "```javascript\nconsole.log('test');\n```",
        ]);

        $response->assertOk();

        $html = $response->json('html');
        expect($html)->toContain('<code');
        expect($html)->toContain('<pre>');
    });
});
