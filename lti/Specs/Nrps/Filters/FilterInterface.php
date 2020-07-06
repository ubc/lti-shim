<?php

namespace UBC\LTI\Specs\Nrps\Filters;

use App\Models\Nrps;

interface FilterInterface
{
    public function filter(array $params, Nrps $nrps): array;
}
