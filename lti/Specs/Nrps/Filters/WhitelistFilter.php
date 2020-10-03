<?php

namespace UBC\LTI\Specs\Nrps\Filters;

use App\Models\Nrps;

use UBC\LTI\Filters\AbstractWhitelistFilter;
use UBC\LTI\Specs\Nrps\Filters\FilterInterface;
use UBC\LTI\Utils\Param;

// Remove any parameters that we do not recognize. This does not check the
// parameter values at all, only looking at the parameter name.
class WhitelistFilter extends AbstractWhitelistFilter implements FilterInterface
{
    protected const LOG_HEADER = 'Whitelist Filter';

    // list of params that show up in NRPS responses
    public const NRPS_PARAMS = [
        Param::ID => 1,
        Param::CONTEXT => 2,
        Param::MEMBERS => 3,
        Param::LINK => 4
    ];

    protected array $whitelists = [
        self::NRPS_PARAMS
    ];

    public function filter(array $params, Nrps $nrps): array
    {
        $this->ltiLog->debug('Running');
        return $this->apply($params);
    }
}
