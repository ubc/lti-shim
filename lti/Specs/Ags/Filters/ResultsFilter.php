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

class ResultsFilter extends AbstractFilter implements FilterInterface
{
    protected const LOG_HEADER = 'Results Filter';

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
        if (!$lineitem) {
            throw new LtiException($this->ltiLog->msg(
                "AGS result missing an associated lineitem."));
        }
        $this->ltiLog->debug('Working', $ags);

        $this->filterIdUrl($params, $ags, $lineitem);
        $this->filterScoreOf($params, $ags, $lineitem);
        $this->filterUserId($params, $ags, $lineitem);

        $this->ltiLog->debug('Completed', $ags);
        return $params;
    }

    private function filterIdUrl(
        array &$params,
        Ags $ags,
        AgsLineitem $lineitem
    ) {
        $resultUrls = [];
        foreach ($params as $result) {
            if (!isset($result[Param::ID]))
                throw new LtiException($this->ltiLog->msg(
                    "AGS result missing an id: " . json_encode($result)));
            $resultUrls[] = $result[Param::ID];
        }

        // like with nrps, there could be a lot of lineitems, so I would like
        // to try to use mass db operations to reduce the number of queries we
        // have to do
        $results = AgsResult::createOrGetAll($resultUrls, $lineitem->id);
        // for faster access, map the result url to the result db entry
        $resultsByResultUrl = [];
        foreach ($results as $result) {
            $resultsByResultUrl[$result->result] = $result;
        }

        // rewrite the result url to shim url
        foreach ($params as &$resultInfo) {
            $result = $resultsByResultUrl[$resultInfo[Param::ID]];
            $resultInfo[Param::ID] = $result->shim_url;
        }
    }

    private function filterScoreOf(
        array &$params,
        Ags $ags,
        AgsLineitem $lineitem
    ) {
        foreach ($params as &$result) {
            if (!isset($result[Param::SCORE_OF])) continue;
            $result[Param::SCORE_OF] = $lineitem->getShimLineitemUrl();
        }
    }

    private function filterUserId(
        array &$params,
        Ags $ags,
        AgsLineitem $lineitem
    ) {
        // grab the real users being referred to
        $userIds = [];
        foreach ($params as $result) {
            if (!isset($result[Param::AGS_USER_ID]))
                throw new LtiException($this->ltiLog->msg(
                    "AGS result missing userId: ". json_encode($result), $ags));
            $userIds[] = $result[Param::AGS_USER_ID];
        }
        $platformId = $ags->deployment->platform_id;
        $realUsers = LtiRealUser::getBySubs($platformId, $userIds);
        // now need to grab the fake users
        $fakeUsers = LtiFakeUser::getByRealUsers($ags->course_context_id,
                                                 $ags->tool_id, $realUsers);
        $fakeUsersByRealSub = [];
        foreach ($fakeUsers as $fakeUser) {
            $fakeUsersByRealSub[$fakeUser->lti_real_user->sub] = $fakeUser;
        }
        // now replace the real users with fake ones
        foreach ($params as &$result) {
            if (!isset($fakeUsersByRealSub[$result[Param::AGS_USER_ID]])) {
                $this->ltiLog->error('AGS result refers to unknown user: ' .
                                     json_encode($result), $ags);
                throw new LtiException($this->ltiLog->msg(
                    "AGS result refers to unkown user, please run NRPS."));
            }
            $fakeUser = $fakeUsersByRealSub[$result[Param::AGS_USER_ID]];
            $result[Param::AGS_USER_ID] = $fakeUser->sub;
        }
    }
}
