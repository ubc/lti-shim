<?php

namespace UBC\LTI\Specs\Ags\Filters;

use App\Models\Ags;
use App\Models\AgsLineitem;

interface FilterInterface
{
    public function filter(
        array $params,
        Ags $ags,
        AgsLineitem $lineitem = null
    ): array;
}
