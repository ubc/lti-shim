<?php

namespace UBC\LTI\Specs\DeepLink\Filters;

use App\Models\DeepLink;

interface FilterInterface
{
    public function filter(array $params, DeepLink $dl): array;
}
