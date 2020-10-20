<?php

namespace UBC\LTI\Specs\Launch\Filters;

use Illuminate\Support\Facades\Log;

use App\Models\LtiSession;
use App\Models\Deployment;

use UBC\LTI\Filters\AbstractWhitelistFilter;
use UBC\LTI\Specs\Launch\Filters\FilterInterface;
use UBC\LTI\Utils\Param;

// Assignment and Grades Service (AGS)
class AgsFilter extends AbstractWhitelistFilter implements FilterInterface
{
    protected const LOG_HEADER = 'Ags Filter';

    protected array $whitelists = [Param::AGS_SCOPES];

    public function filter(array $params, LtiSession $session): array
    {
        // do nothing if platform doesn't support NRPS
        if (!isset($params[Param::AGS_CLAIM_URI])) {
            $this->ltiLog->debug('Skipping', $session);
            return $params;
        }
        $this->ltiLog->debug('Running', $session);

        //$this->ltiLog->debug('Nrps: ' . $nrps->id . ' URL: ' .
        //    $nrps->context_membership_url, $session);

        // scope tells the tool what AGS operation are available,
        // and are used when the tool requests an access token.
        // only scopes we can handle should be allowed through
        $scopes = $params[Param::AGS_CLAIM_URI][Param::SCOPE];
        $scopes = array_keys($this->apply(array_flip($scopes)));

        $filteredClaim = [
            Param::SCOPE => $scopes
        ];
        $params[Param::AGS_CLAIM_URI] = $filteredClaim;

        return $params;
    }
}
