<?php

namespace UBC\LTI\Specs\Launch\Filters;

use Illuminate\Support\Facades\Log;

use App\Models\Ags;
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

        // grab the lineitems, this is required by spec, drop AGS if not found
        if (!isset($params[Param::AGS_CLAIM_URI][Param::AGS_LINEITEMS])) {
            $this->ltiLog->error('Missing lineitems in AGS claim.', $session);
            unset($params[Param::AGS_CLAIM_URI]);
            return $params;
        }
        $lineitems = $params[Param::AGS_CLAIM_URI][Param::AGS_LINEITEMS];
        $lineitem = '';
        if (isset($params[Param::AGS_CLAIM_URI][Param::AGS_LINEITEM])) {
            $lineitem = $params[Param::AGS_CLAIM_URI][Param::AGS_LINEITEM];
        }

        // scope tells the tool what AGS operation are available,
        // and are used when the tool requests an access token.
        // only scopes we can handle should be allowed through
        $scopes = $params[Param::AGS_CLAIM_URI][Param::SCOPE];
        $scopes = array_keys($this->apply(array_flip($scopes)));

        $this->ltiLog->debug('lineitems: ' . $lineitems . ' lineitem: ' .
            $lineitem . ' scopes: ' . json_encode($scopes), $session);

        $ags = Ags::createOrGet(
            $lineitems,
            $lineitem,
            $session->course_context_id,
            $session->deployment_id,
            $session->tool_id,
            $scopes
        );
        $this->ltiLog->debug('ags entry found', $session, $ags);

        $filteredClaim = [
            Param::SCOPE => $scopes,
            Param::AGS_LINEITEMS => $ags->getShimLineitemsUrl()
        ];
        if ($lineitem) {
            $filteredClaim[Param::AGS_LINEITEM] = $ags->getShimLineitemUrl();
        }
        $params[Param::AGS_CLAIM_URI] = $filteredClaim;

        return $params;
    }
}
