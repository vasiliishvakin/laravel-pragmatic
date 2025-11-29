<?php

declare(strict_types=1);

namespace Pragmatic\Hashing;

use Illuminate\Contracts\Hashing\Hasher;

class FastHasher implements Hasher
{
    protected readonly string $algo;

    public function __construct(string $algo = 'xxh3')
    {
        $this->algo = $algo;
    }

    /**
     * Return info about hash (for compatibility with Hasher interface).
     */
    public function info($hashedValue): array
    {
        return [
            'algo' => $this->algo,
            'note' => 'Non-cryptographic fast hash',
        ];
    }

    /**
     * Create a fast, non-cryptographic hash of a value.
     */
    public function make($value, array $options = []): string
    {
        return hash($this->algo, $value);
    }

    /**
     * Compare values (timing-safe, but not cryptographically secure).
     */
    public function check($value, $hashedValue, array $options = []): bool
    {
        return hash_equals($this->make($value), $hashedValue);
    }

    /**
     * Fast hashes never need rehashing.
     */
    public function needsRehash($hashedValue, array $options = []): bool
    {
        return false;
    }
}
