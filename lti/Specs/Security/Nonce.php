<?php
namespace UBC\LTI\Specs\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

use UBC\LTI\LTIException;

class Nonce
{
    public const EXPIRY_TIME = 3600; // default nonce expiration
    public const NONCE_LENGTH = 16; // number of bytes to generate for nonce
    public const NONCE_STORE = 'nonce';

    /**
     * Generate a nonce string. The string is stored in cache and will expire
     * after the given number of seconds. The nonce string itself is used as
     * the cache key, the cache value is an integer indicating whether the
     * nonce has been used: 0 for unused, >=1 for used. It's an integer so we
     * can use the cache increment/decrement methods, cause I'm not sure if the
     * expiration will get overridden if I use Cache::put().
     *
     * $expiresIn - Number of seconds this nonce will be valid for
     */
    public static function create(int $expiresIn=self::EXPIRY_TIME): string
    {
        $store = Cache::store(self::NONCE_STORE);
        $nonce = bin2hex(random_bytes(self::NONCE_LENGTH));
        if ($store->add($nonce, 0, $expiresIn)) {
            return $nonce;
        }
        Log::error("Generated a duplicate nonce, is this even possible? " .
                   $nonce);
        throw new LTIException("Error generating a nonce.");
    }

    /**
     * Indicates that the given nonce has been used and should not be valid
     * anymore.
     */
    public static function used(string $nonce)
    {
        $store = Cache::store(self::NONCE_STORE);
        // ignore nonces that we don't have in the cache, they should be
        // rejected as invalid nonces
        if ($store->has($nonce)) $store->increment($nonce);
    }

    /**
     * Returns true if the nonce is valid. A valid nonce is one that can be
     * found in the cache and hasn't been used yet.
     */
    public static function isValid(string $nonce): bool
    {
        $store = Cache::store(self::NONCE_STORE);
        // get() will return 1 if nonce is not in the cache
        return ($store->get($nonce, 1) < 1);
    }
}
