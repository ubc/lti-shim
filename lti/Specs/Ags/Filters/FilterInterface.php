<?php

namespace UBC\LTI\Specs\Ags\Filters;

use App\Models\Ags;

interface FilterInterface
{
    public function filter(array $params, Ags $ags): array;
}
