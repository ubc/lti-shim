<?php

namespace UBC\LTI\Specs\Nrps\Filters;

use Illuminate\Support\Facades\Log;

use League\Uri\Components\Query;
use League\Uri\Uri;
use League\Uri\Contracts\UriException;

use GuzzleHttp\Psr7;

use App\Models\LtiSession;
use App\Models\Deployment;
use App\Models\Nrps;

use UBC\LTI\Filters\AbstractFilter;
use UBC\LTI\Specs\Nrps\Filters\FilterInterface;
use UBC\LTI\Utils\Param;

// NRPS pagination is passed in the Link header following RFC5988. I was hoping
// to find a nice PHP library to deal with this, but couldn't find any. So this
// is only a very basic implementation, with support for only the 'rel' link
// param.
class PaginationFilter extends AbstractFilter implements FilterInterface
{
    protected const LOG_HEADER = 'Pagination Filter';

    public function filter(array $params, Nrps $nrps): array
    {
        if (!isset($params[Param::LINK])) {
            $this->ltiLog->debug('Skipping', $nrps);
            return $params;
        }
        $this->ltiLog->debug('Trying', $nrps);

        // there could be multiple links in the header, so we want to parse them
        // out into an array of individual links
        $links = Psr7\parse_header($params[Param::LINK]);

        // create or get an NRPS entry for each link
        foreach ($links as $key => $link) {
            // the url is bracketed by <>, remove them
            $url = trim($link[0], '<>');
            $linkNrps = Nrps::createOrGet($url, $nrps->course_context_id,
                $nrps->deployment_id, $nrps->tool_id);
            $links[$key]['nrps'] = $linkNrps;
        }

        // rebuild the Link header string using shim URLs
        $linkHeader = '';
        foreach ($links as $link) {
            $linkNrps = $link['nrps'];
            $linkHeader .= '<' . $linkNrps->getShimUrl() . '>;';
            $rel = '';
            if (isset($link['rel'])) {
                $rel = $link['rel'];
                $linkHeader .= ' rel="' . $rel . '"';
            }
            $linkHeader .= ',';
            $this->ltiLog->debug("Pagination Nrps $rel: " . $linkNrps->id .
                ' for url: ' . $url, $nrps);
        }
        // remove last comma
        $linkHeader = trim($linkHeader, ',');

        $params[Param::LINK] = $linkHeader;

        return $params;
    }
}
