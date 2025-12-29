<?php

declare(strict_types=1);

use App\Support\IpAnonymizer;

describe('IpAnonymizer', function (): void {
    describe('IPv4', function (): void {
        it('anonymizes IPv4 addresses', function (): void {
            $result = IpAnonymizer::anonymize('192.168.1.123');

            expect($result)->toBe('192.168.1.0');
        });

        it('handles already anonymized addresses', function (): void {
            $result = IpAnonymizer::anonymize('10.0.0.0');

            expect($result)->toBe('10.0.0.0');
        });

        it('handles edge cases', function (): void {
            expect(IpAnonymizer::anonymize('255.255.255.255'))->toBe('255.255.255.0');
            expect(IpAnonymizer::anonymize('0.0.0.1'))->toBe('0.0.0.0');
        });
    });

    describe('IPv6', function (): void {
        it('anonymizes IPv6 addresses', function (): void {
            $result = IpAnonymizer::anonymize('2001:0db8:85a3:0000:0000:8a2e:0370:7334');

            expect($result)->toBe('2001:db8:85a3::');
        });

        it('handles compressed IPv6', function (): void {
            $result = IpAnonymizer::anonymize('2001:db8::1');

            expect($result)->toBe('2001:db8::');
        });

        it('handles loopback', function (): void {
            $result = IpAnonymizer::anonymize('::1');

            expect($result)->toBe('::');
        });
    });

    describe('edge cases', function (): void {
        it('returns null for null input', function (): void {
            expect(IpAnonymizer::anonymize(null))->toBeNull();
        });

        it('returns null for empty string', function (): void {
            expect(IpAnonymizer::anonymize(''))->toBeNull();
        });

        it('returns null for invalid IPv4', function (): void {
            expect(IpAnonymizer::anonymize('not.an.ip'))->toBeNull();
            expect(IpAnonymizer::anonymize('256.1.2.3'))->toBeNull();
        });

        it('returns null for invalid IPv6', function (): void {
            expect(IpAnonymizer::anonymize('not:an:ipv6'))->toBeNull();
        });
    });

    describe('validation', function (): void {
        it('validates IPv4 addresses', function (): void {
            expect(IpAnonymizer::isValid('192.168.1.1'))->toBeTrue();
            expect(IpAnonymizer::isValid('invalid'))->toBeFalse();
        });

        it('validates IPv6 addresses', function (): void {
            expect(IpAnonymizer::isValid('2001:db8::1'))->toBeTrue();
            expect(IpAnonymizer::isValid('::1'))->toBeTrue();
        });

        it('detects IPv6', function (): void {
            expect(IpAnonymizer::isIpv6('2001:db8::1'))->toBeTrue();
            expect(IpAnonymizer::isIpv6('192.168.1.1'))->toBeFalse();
            expect(IpAnonymizer::isIpv6(null))->toBeFalse();
        });
    });
});
