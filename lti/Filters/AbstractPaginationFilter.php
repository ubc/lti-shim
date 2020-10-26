<?php

namespace UBC\LTI\Filters;

use Illuminate\Support\Facades\Log;

use GuzzleHttp\Psr7;

use UBC\LTI\Filters\AbstractFilter;

// Pagination is passed in the Link header following RFC5988. I was hoping to
// find a nice PHP library to deal with this, but couldn't find any. So this is
// only a very basic implementation, with support for only the 'rel' link
// param.
abstract class AbstractPaginationFilter extends AbstractFilter
{
    protected const LOG_HEADER = 'Pagination Filter';
    protected const REL = 'rel';
    protected const URL = 'url';

    /**
     * given the value of the link header as a string, parse out the urls and
     * the rel keyboard into an array, e.g.:
     * [
     *  [
     *      'url' => 'http://example.com/someurl/1',
     *      'rel' => 'next'
     *  ],
     *  [
     *      'url' => 'http://example.com/someurl/2',
     *      'rel' => 'prev'
     *  ]
     * ]
     */
    protected function parseLinkHeader(string $linkHeader): array
    {
        // there could be multiple links in the header, so we want to parse them
        // out into an array of individual links
        $links = Psr7\parse_header($linkHeader);
        $ret = [];
        foreach ($links as $key => $link) {
            // the url is bracketed by <>, remove them
            $url = trim($link[0], '<>');
            $entry = [static::URL => $url];
            if (isset($link[static::REL]))
                $entry[static::REL] = $link[static::REL];
            $ret[] = $entry;
        }
        return $ret;
    }

    /**
     * given an array in the same structure as that returned from parseLinkHeader,
     * convert it back into a link header string
     */
    protected function createLinkHeader(array $links): string
    {
        $linkHeader = '';
        foreach ($links as $link) {
            $url = $link[static::URL];
            $linkHeader .= '<' . $url . '>;';
            $rel = '';
            if (isset($link['rel'])) {
                $rel = $link['rel'];
                $linkHeader .= ' rel="' . $rel . '"';
            }
            $linkHeader .= ',';
            $this->ltiLog->debug("Pagination rel $rel: " . $url);
        }
        // remove last comma
        $linkHeader = trim($linkHeader, ',');

        return $linkHeader;
    }
}
