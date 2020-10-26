<?php

namespace UBC\LTI\Specs\Nrps\Filters;

use Illuminate\Support\Facades\Log;

use App\Models\Nrps;

use UBC\LTI\Filters\AbstractPaginationFilter;
use UBC\LTI\Specs\Nrps\Filters\FilterInterface;
use UBC\LTI\Utils\Param;

// NRPS pagination is passed in the Link header, see AbstractPaginationFilter
// for how we decode and re-encode into Link header format.
class PaginationFilter extends AbstractPaginationFilter
                       implements FilterInterface
{
    protected const LOG_HEADER = 'Pagination Filter';

    public function filter(array $params, Nrps $nrps): array
    {
        if (!isset($params[Param::LINK])) {
            $this->ltiLog->debug('Skipping', $nrps);
            return $params;
        }
        $this->ltiLog->debug('Trying', $nrps);

        $links = $this->parseLinkHeader($params[Param::LINK]);

        // create or get an NRPS entry for each link
        foreach ($links as $key => $link) {
            $linkNrps = Nrps::createOrGet($link[static::URL],
                $nrps->course_context_id, $nrps->deployment_id, $nrps->tool_id);
            $links[$key][static::URL] = $linkNrps->getShimUrl();
        }

        $params[Param::LINK] = $this->createLinkHeader($links);

        return $params;
    }
}
