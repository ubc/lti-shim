<?php

namespace UBC\LTI\Specs\Ags\Filters;

use Illuminate\Support\Facades\Log;

use App\Models\Ags;
use App\Models\AgsLineitem;

use UBC\LTI\Filters\AbstractFilter;
use UBC\LTI\Specs\Ags\Filters\FilterInterface;
use UBC\LTI\Utils\Param;

class LineitemsFilter extends AbstractFilter implements FilterInterface
{
    protected const LOG_HEADER = 'Lineitems Filter';

    public function filter(
        array $params,
        Ags $ags,
        AgsLineitem $agsLineitem = null
    ): array {
        // check required fields exist
        if (!$params) {
            $this->ltiLog->debug('Empty lineitems, skipping', $ags);
            return $params;
        }
        $this->ltiLog->debug('Working', $ags);
        $lineitemUrls = [];
        foreach ($params as &$lineitemInfo) {
            $lineitemUrls[] = $lineitemInfo[Param::ID];
        }

        // like with nrps, there could be a lot of lineitems, so I would like
        // to try to use mass db operations to reduce the number of queries we
        // have to do
        $lineitems = AgsLineitem::createOrGetAll($lineitemUrls, $ags->id);
        // for faster access, map the lineitem url to the lineitem db entry
        $lineitemsByLineitemUrl = [];
        foreach ($lineitems as $lineitem) {
            $lineitemsByLineitemUrl[$lineitem->lineitem] = $lineitem;
        }

        // rewrite the lineitem url to shim url
        foreach ($params as &$lineitemInfo) {
            $lineitem = $lineitemsByLineitemUrl[$lineitemInfo[Param::ID]];
            $lineitemInfo[Param::ID] = $lineitem->getShimLineitemUrl();
        }

        return $params;
    }
}
