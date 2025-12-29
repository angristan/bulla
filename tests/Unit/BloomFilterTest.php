<?php

declare(strict_types=1);

use App\Support\BloomFilter;

describe('BloomFilter', function (): void {
    it('reports false for items not added', function (): void {
        $filter = new BloomFilter;

        expect($filter->mightContain('test'))->toBeFalse();
        expect($filter->mightContain('another'))->toBeFalse();
    });

    it('reports true for items after adding', function (): void {
        $filter = new BloomFilter;

        $filter->add('test');

        expect($filter->mightContain('test'))->toBeTrue();
        expect($filter->mightContain('another'))->toBeFalse();
    });

    it('can be serialized and restored', function (): void {
        $filter = new BloomFilter;
        $filter->add('voter1');
        $filter->add('voter2');

        $binary = $filter->toBinary();
        $restored = BloomFilter::fromBinary($binary);

        expect($restored->mightContain('voter1'))->toBeTrue();
        expect($restored->mightContain('voter2'))->toBeTrue();
        expect($restored->mightContain('voter3'))->toBeFalse();
    });

    it('handles null binary input', function (): void {
        $filter = BloomFilter::fromBinary(null);

        expect($filter->mightContain('test'))->toBeFalse();
    });

    it('handles empty binary input', function (): void {
        $filter = BloomFilter::fromBinary('');

        expect($filter->mightContain('test'))->toBeFalse();
    });

    it('creates consistent voter IDs', function (): void {
        $id1 = BloomFilter::createVoterId('192.168.1.1', 'Mozilla/5.0');
        $id2 = BloomFilter::createVoterId('192.168.1.1', 'Mozilla/5.0');
        $id3 = BloomFilter::createVoterId('192.168.1.2', 'Mozilla/5.0');

        expect($id1)->toBe($id2);
        expect($id1)->not->toBe($id3);
    });

    it('handles many items with low false positive rate', function (): void {
        $filter = new BloomFilter;

        // Add 100 items
        for ($i = 0; $i < 100; $i++) {
            $filter->add("item_$i");
        }

        // Check all added items are found
        for ($i = 0; $i < 100; $i++) {
            expect($filter->mightContain("item_$i"))->toBeTrue();
        }

        // Check false positive rate for non-existent items
        $falsePositives = 0;
        for ($i = 100; $i < 1100; $i++) {
            if ($filter->mightContain("item_$i")) {
                $falsePositives++;
            }
        }

        // Should be under 5% false positive rate
        expect($falsePositives)->toBeLessThan(50);
    });
});
