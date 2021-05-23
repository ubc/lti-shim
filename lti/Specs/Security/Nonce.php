<?php
namespace UBC\LTI\Specs\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\Param;

class Nonce
{
    public const EXPIRY_TIME = Param::EXP_TIME; // default nonce expiration
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
     * @param int $expiresIn Number of seconds this nonce will be valid for
     *
     * @return the nonce string
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
        throw new LtiException("Error generating a nonce.");
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
     * For nonces not generated by us but we still need to make sure that they
     * aren't reused. E.g.: so people can't replay attack access token requests
     *
     * Note that this stores the nonce as if it was just created, so you will
     * need to use the used() function to mark it used and isValid() to check
     * validity as always.
     *
     * Will ignore nonces that have already been seen. Cause that might mean
     * we've already marked that one as used.
     */
    public static function store(string $nonce, int $expiresIn)
    {
        $store = Cache::store(self::NONCE_STORE);
        if ($store->has($nonce)) return;
        if ($expiresIn <= 0) return; // don't care about already expired nonces
        $store->add($nonce, 0, $expiresIn);
    }


    /**
     * @return true if the nonce is valid. A valid nonce is one that can be
     * found in the cache and hasn't been used yet.
     */
    public static function isValid(string $nonce): bool
    {
        $store = Cache::store(self::NONCE_STORE);
        // get() will return 1 if nonce is not in the cache
        return ($store->get($nonce, 1) < 1);
    }
}
