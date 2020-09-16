<?php

namespace UBC\LTI\Specs\Launch\Filters;

use Illuminate\Support\Facades\Log;

use App\Models\LtiSession;
use App\Models\Deployment;
use App\Models\Nrps;

use UBC\LTI\Filters\AbstractFilter;
use UBC\LTI\Specs\Launch\Filters\FilterInterface;
use UBC\LTI\Param;

// Names and Role Provisioning Service (NRPS)
// rewrites the NRPS urls provided by the original platform into the shim's urls
class NrpsFilter extends AbstractFilter implements FilterInterface
{
    protected const LOG_HEADER = 'Nrps Filter';

    public function filter(array $params, LtiSession $session): array
    {
        // do nothing if platform doesn't support NRPS
        if (!isset($params[Param::NRPS_CLAIM_URI])) {
            $this->ltiLog->debug('Skipping', $session);
            return $params;
        }
        $this->ltiLog->debug('Running', $session);

        $deployment = $session->deployment;
        $tool = $session->tool;

        $origClaim = $params[Param::NRPS_CLAIM_URI];

        // The nrps table is where we can store data about the original nrps
        // service call.
        $nrps = Nrps::createOrGet(
            $origClaim[Param::CONTEXT_MEMBERSHIPS_URL],
            $session->course_context_id,
            $deployment->id,
            $tool->id
        );
        $this->ltiLog->debug('Nrps: ' . $nrps->id, $session);

        // replace the original endpoint with the one on the shim
        $filteredClaim = [
            Param::CONTEXT_MEMBERSHIPS_URL => $nrps->getShimUrl(),
            Param::SERVICE_VERSIONS => ['2.0']
        ];
        $params[Param::NRPS_CLAIM_URI] = $filteredClaim;

        return $params;
    }
}
