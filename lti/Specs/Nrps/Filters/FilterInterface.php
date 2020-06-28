<?php

namespace UBC\LTI\Specs\Nrps\Filters;

use App\Models\Tool;

interface FilterInterface
{
    public function filter(array $params, int $deploymentId, int $toolId): array;
}
