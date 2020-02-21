<?php

namespace UBC\LTI\Filters;

use App\Models\LtiSession;

interface FilterInterface
{
    public function filter(array $params, LtiSession $session): array;
}
