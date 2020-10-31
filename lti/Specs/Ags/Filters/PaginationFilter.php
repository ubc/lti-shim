<?php

namespace UBC\LTI\Specs\Ags\Filters;

use Illuminate\Support\Facades\Log;

use App\Models\Ags;
use App\Models\AgsLineitem;

use UBC\LTI\Filters\AbstractPaginationFilter;
use UBC\LTI\Specs\Ags\Filters\FilterInterface;
use UBC\LTI\Utils\Param;

class PaginationFilter extends AbstractPaginationFilter
                       implements FilterInterface
{
    protected const LOG_HEADER = 'Pagination Filter';

    public function filter(
        array $params,
        Ags $ags,
        AgsLineitem $lineitem = null
    ): array {
        if (!isset($params[Param::LINK])) {
            $this->ltiLog->debug('Skipping', $ags);
            return $params;
        }
        $this->ltiLog->debug('Trying', $ags);

        $links = $this->parseLinkHeader($params[Param::LINK]);

        // create or get an AGS entry for each link
        foreach ($links as $key => $link) {
            $linkAgs = Ags::createOrGet(
                $link[static::URL],
                $ags->course_context_id,
                $ags->deployment_id,
                $ags->tool_id,
                $ags->scopes
            );
            $links[$key][static::URL] = $linkAgs->getShimLineitemsUrl();
        }

        $params[Param::LINK] = $this->createLinkHeader($links);

        return $params;
    }
}
