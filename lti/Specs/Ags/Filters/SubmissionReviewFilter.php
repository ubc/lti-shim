<?php

namespace UBC\LTI\Specs\Ags\Filters;

use Illuminate\Support\Facades\Log;

use App\Models\Ags;
use App\Models\AgsLineitem;

use UBC\LTI\Filters\AbstractFilter;
use UBC\LTI\Specs\Ags\Filters\FilterInterface;
use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\Param;

/**
 * Filters Gradebook Message's 'submissionReview' lineitem property.
 * Note that this filter needs to be run when creating a lineitem, before
 * we proxy the lineitem to the platform for the actual creation. Since we want
 * to make sure 'url' filtering works.
 */
class SubmissionReviewFilter extends AbstractFilter implements FilterInterface
{
    protected const LOG_HEADER = 'submissionReview Filter';

    public function filter(
        array $params,
        Ags $ags,
        AgsLineitem $agsLineitem = null
    ): array {
        $this->ltiLog->debug('Running');
        // replace data in each of the lineitems
        foreach ($params as &$lineitemInfo) {
            // filter submissionReview if present, part of Gradebook Messages
            if (isset($lineitemInfo[Param::SUBMISSION_REVIEW])) {
                $subReview = $lineitemInfo[Param::SUBMISSION_REVIEW];
                if (!isset($subReview[Param::REVIEWABLE_STATUS])) {
                    throw new LtiException($this->ltiLog->msg(
                        'Missing submissionReview required param ' .
                        '"reviewableStatus"'));
                }
                // TODO: support URL param filtering
                if (isset($subReview[Param::URL])) {
                    // if the platform wants to customize the launch url, we
                    // would need to replace it with a launch url on the shim,
                    // which isn't supported right now
                    $this->ltiLog->error('Unsupported Gradebook Message, ' .
                                         'requires custom launch url');
                    unset($lineitemInfo[Param::SUBMISSION_REVIEW]);
                }
            }
        }

        return $params;
    }
}
