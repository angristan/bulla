<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Anonymize IP addresses by zeroing out the last portion.
 * - IPv4: /24 mask (last octet zeroed)
 * - IPv6: /48 mask (last 80 bits zeroed)
 */
class IpAnonymizer
{
    /**
     * Anonymize an IP address.
     */
    public static function anonymize(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        // Check if IPv6
        if (str_contains($ip, ':')) {
            return self::anonymizeIpv6($ip);
        }

        return self::anonymizeIpv4($ip);
    }

    /**
     * Anonymize an IPv4 address by zeroing the last octet.
     * Example: 192.168.1.123 -> 192.168.1.0
     */
    private static function anonymizeIpv4(string $ip): ?string
    {
        // Validate IP address first
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return null;
        }

        $parts = explode('.', $ip);
        $parts[3] = '0';

        return implode('.', $parts);
    }

    /**
     * Anonymize an IPv6 address by zeroing everything after the first 48 bits.
     * Example: 2001:0db8:85a3:0000:0000:8a2e:0370:7334 -> 2001:db8:85a3::
     */
    private static function anonymizeIpv6(string $ip): ?string
    {
        // Expand the IPv6 address to full form
        $packed = @inet_pton($ip);

        if ($packed === false) {
            return null;
        }

        // Zero out everything after the first 6 bytes (48 bits)
        for ($i = 6; $i < 16; $i++) {
            $packed[$i] = "\0";
        }

        $expanded = inet_ntop($packed);

        if ($expanded === false) {
            return null;
        }

        return $expanded;
    }

    /**
     * Check if an IP address is valid.
     */
    public static function isValid(?string $ip): bool
    {
        if ($ip === null || $ip === '') {
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Check if an IP address is IPv6.
     */
    public static function isIpv6(?string $ip): bool
    {
        if ($ip === null) {
            return false;
        }

        return str_contains($ip, ':');
    }
}
