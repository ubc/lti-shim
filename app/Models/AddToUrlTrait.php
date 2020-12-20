<?php
namespace App\Models;

use League\Uri\Components\Query;
use League\Uri\Uri;
use League\Uri\UriModifier;

trait AddToUrlTrait
{
    /**
     * The urls might have existing GET queries. If we want to add additional
     * queries or if we want to append to the path, then we need to rebuild the
     * URL.
     */
    private function addToUrl(
        string $url,
        array $queries = [],
        string $path = ''
    ): string {
        $uri = Uri::createFromString($url);
        if ($path) $uri = UriModifier::appendSegment($uri, $path);
        if ($queries) {
            $query = Query::createFromParams($queries);
            $uri = UriModifier::mergeQuery($uri, $query);
        }

        return $uri;
    }
}
