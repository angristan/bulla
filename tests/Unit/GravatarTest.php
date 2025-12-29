<?php

declare(strict_types=1);

use App\Support\Gravatar;

describe('Gravatar', function (): void {
    it('generates a gravatar URL for email', function (): void {
        $url = Gravatar::url('test@example.com');

        expect($url)->toContain('https://www.gravatar.com/avatar/');
        expect($url)->toContain('d=identicon');
    });

    it('normalizes email to lowercase', function (): void {
        $url1 = Gravatar::url('TEST@EXAMPLE.COM');
        $url2 = Gravatar::url('test@example.com');

        expect($url1)->toBe($url2);
    });

    it('trims whitespace from email', function (): void {
        $url1 = Gravatar::url('  test@example.com  ');
        $url2 = Gravatar::url('test@example.com');

        expect($url1)->toBe($url2);
    });

    it('handles null email', function (): void {
        $url = Gravatar::url(null);

        expect($url)->toContain('https://www.gravatar.com/avatar/');
        expect($url)->toContain('f=y'); // Force default
    });

    it('handles empty email', function (): void {
        $url = Gravatar::url('');

        expect($url)->toContain('https://www.gravatar.com/avatar/');
        expect($url)->toContain('f=y');
    });

    it('respects size parameter', function (): void {
        $url = Gravatar::url('test@example.com', 200);

        expect($url)->toContain('s=200');
    });

    it('creates consistent IP hashes', function (): void {
        $hash1 = Gravatar::hashForIp('192.168.1.1', 'salt');
        $hash2 = Gravatar::hashForIp('192.168.1.1', 'salt');

        expect($hash1)->toBe($hash2);
    });

    it('creates different hashes for different IPs', function (): void {
        $hash1 = Gravatar::hashForIp('192.168.1.1', 'salt');
        $hash2 = Gravatar::hashForIp('192.168.1.2', 'salt');

        expect($hash1)->not->toBe($hash2);
    });

    it('creates different hashes for different salts', function (): void {
        $hash1 = Gravatar::hashForIp('192.168.1.1', 'salt1');
        $hash2 = Gravatar::hashForIp('192.168.1.1', 'salt2');

        expect($hash1)->not->toBe($hash2);
    });
});
