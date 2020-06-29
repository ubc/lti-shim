<?php

namespace UBC\LTI\Specs\Nrps\Filters;

use Illuminate\Support\Facades\Log;

use App\Models\LtiSession;
use App\Models\Deployment;
use App\Models\Nrps;

use UBC\LTI\Specs\Nrps\Filters\FilterInterface;
use UBC\LTI\Param;

// Names and Role Provisioning Service (NRPS)
// rewrites the NRPS urls provided by the original platform into the shim's urls
class NrpsUrlFilter implements FilterInterface
{
    public function filter(
        array $params,
        int $deploymentId,
        int $toolId
    ): array {
        // the NRPS url is written in the Param::ID field in the NRPS response
        // nothing to do if field doesn't exist
        if (!isset($params[Param::ID])) return $params;

        // note that the NRPS spec doesn't explicitly say that the NRPS
        // endpoint is put into Param::ID field. However, the example puts the
        // NRPS endpoint into the Param::ID field, so maybe it's written in
        // some other spec.
        $url = $params[Param::ID];
        $nrps = Nrps::getByUrl($url, $deploymentId, $toolId);
        if (!$nrps) {
            // for some reason, they're not using an URL for the id, log it
            // so we can see what they're using
            Log::error("NRPS response ID not using URL: " . $url);
            // replace it with some random value for now cause I have no idea
            // what we should do yet
            $params[Param::ID] = bin2hex(random_bytes(32));
            return $params;
        }

        // replace the original endpoint with the one on the shim
        $params[Param::ID] = $nrps->shim_url;
        return $params;
    }
}
