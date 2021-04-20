<?php
namespace UBC\LTI\Utils;

use Illuminate\Support\Facades\Log;

use Jose\Component\Core\JWK;
use Jose\Easy\Build;
use Jose\Easy\JWT;
use Jose\Easy\Load;

use League\Uri\Uri;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\Param;

/**
 * For repeated common operations using the League\Uri library.
 */
class UriUtil
{
    /**
     * Given two URLs, check to make sure they're pointing to the same site.
     *
     * We're basically just checking that the scheme, host, and port are the
     * same for both URLs
     */
    public static function isSameSite(string $urlStr1, string $urlStr2): bool
    {
        $uri1 = Uri::createFromString($urlStr1);
        $uri2 = Uri::createFromString($urlStr2);

        if ($uri1->getScheme() == $uri2->getScheme() &&
            $uri1->getHost() == $uri2->getHost() &&
            $uri1->getPort() == $uri2->getPort()) {
            return true;
        }

        return false;
    }
}
