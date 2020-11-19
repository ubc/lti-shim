<?php

namespace UBC\LTI\Specs\Ags\Filters;

use Illuminate\Support\Facades\Log;

use App\Models\Ags;
use App\Models\AgsLineitem;
use App\Models\AgsResult;

use UBC\LTI\Filters\AbstractPaginationFilter;
use UBC\LTI\Specs\Ags\Filters\FilterInterface;
use UBC\LTI\Utils\Param;

class ResultPaginationFilter extends AbstractPaginationFilter
                               implements FilterInterface
{
    protected const LOG_HEADER = 'Result Pagination Filter';

    public function filter(
        array $params,
        Ags $ags,
        AgsLineitem $lineitem = null
    ): array {
        if (!isset($params[Param::LINK])) {
            $this->ltiLog->debug('Skipping', $ags, $lineitem);
            return $params;
        }
        $this->ltiLog->debug('Trying', $ags);

        $links = $this->parseLinkHeader($params[Param::LINK]);

        // create or get an AGS entry for each link
        foreach ($links as $key => $link) {
            $linkResult = AgsResult::createOrGet($link[static::URL],
                                                 $lineitem->id);
            $links[$key][static::URL] = $linkResult->shim_url;
        }

        $params[Param::LINK] = $this->createLinkHeader($links);

        $this->ltiLog->debug('Completed', $ags, $lineitem);
        return $params;
    }
}
