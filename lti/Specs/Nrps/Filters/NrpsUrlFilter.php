<?php

namespace UBC\LTI\Specs\Nrps\Filters;

use Illuminate\Support\Facades\Log;

use League\Uri\Components\Query;
use League\Uri\Uri;
use League\Uri\Contracts\UriException;

use App\Models\LtiSession;
use App\Models\Deployment;
use App\Models\Nrps;

use UBC\LTI\Filters\AbstractFilter;
use UBC\LTI\Specs\Nrps\Filters\FilterInterface;
use UBC\LTI\Param;

// Names and Role Provisioning Service (NRPS)
// rewrites the NRPS urls provided by the original platform into the shim's urls
class NrpsUrlFilter extends AbstractFilter implements FilterInterface
{
    protected const LOG_HEADER = 'NRPS URL Filter';

    public function filter(array $params, Nrps $nrps): array
    {
        // the NRPS url is written in the Param::ID field in the NRPS response
        // nothing to do if field doesn't exist
        if (!isset($params[Param::ID])) {
            $this->ltiLog->debug('Skipping', $nrps);
            return $params;
        }
        $this->ltiLog->debug('Trying', $nrps);

        // note that the NRPS spec doesn't explicitly say that the NRPS
        // endpoint is put into Param::ID field. However, the example puts the
        // NRPS endpoint into the Param::ID field, so maybe it's written in
        // some other spec.
        $url = $params[Param::ID];

        $queries = [];
        try {
            $uri = Uri::createFromString($url);
            $query = Query::createFromUri($uri);
            // pull the NRPS pagination/filter queries from the URI so we can
            // attach them to the shim NRPS url
            if ($query->get(Param::LIMIT)) {
                $queries[Param::LIMIT] = $query->get(Param::LIMIT);
            }
            if ($query->get(Param::ROLE)) {
                $queries[Param::ROLE] = $query->get(Param::ROLE);
            }
        }
        catch (UriException $e) {
            $this->ltiLog->error("Invalid NRPS URL response: " . $url, $nrps);
        }

        $this->ltiLog->debug('Queries used: ' . json_encode($queries), $nrps);

        // replace the original endpoint with the one on the shim
        $params[Param::ID] = $nrps->getShimUrl($queries);
        return $params;
    }
}
