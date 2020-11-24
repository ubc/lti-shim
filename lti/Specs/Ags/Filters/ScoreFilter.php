<?php

namespace UBC\LTI\Specs\Ags\Filters;

use Illuminate\Support\Facades\Log;

use App\Models\Ags;
use App\Models\AgsLineitem;
use App\Models\AgsResult;
use App\Models\LtiRealUser;
use App\Models\LtiFakeUser;

use UBC\LTI\Filters\AbstractFilter;
use UBC\LTI\Specs\Ags\Filters\FilterInterface;
use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\Param;

class ScoreFilter extends AbstractFilter implements FilterInterface
{
    protected const LOG_HEADER = 'Score Filter';

    public function filter(
        array $params,
        Ags $ags,
        AgsLineitem $lineitem = null
    ): array {
        // check required fields exist
        if (!$params) {
            $this->ltiLog->debug('Empty results, skipping', $ags);
            return $params;
        }
        if (!isset($params[Param::RESULT_URL])) {
            // resultUrl isn't even in the spec, this is completely
            // undefined behaviour, just returning empty for now
            $this->ltiLog->debug('No resultUrl, returning empty', $ags);
            return [];
        }
        $this->ltiLog->debug('Working', $ags);

        $result = AgsResult::createOrGet($params[Param::RESULT_URL],
                                         $lineitem->id);

        $this->ltiLog->debug('Completed', $ags);
        return [Param::RESULT_URL => $result->shim_url];
    }
}
