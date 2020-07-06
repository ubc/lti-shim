<?php

namespace UBC\LTI\Specs\Nrps\Filters;

use App\Models\Nrps;

use UBC\LTI\Filters\AbstractWhitelistFilter;
use UBC\LTI\Specs\Nrps\Filters\FilterInterface;
use UBC\LTI\Param;

// Remove any parameters that we do not recognize. This does not check the
// parameter values at all, only looking at the parameter name.
class WhitelistFilter extends AbstractWhitelistFilter implements FilterInterface
{
    // list of params that show up in NRPS responses
    public const NRPS_PARAMS = [
        Param::ID => 1,
        Param::CONTEXT => 2,
        Param::MEMBERS => 3
    ];

    protected array $whitelists = [
        self::NRPS_PARAMS
    ];
    
    public function filter(array $params, Nrps $nrps): array
    {
        return $this->apply($params);
    }
}