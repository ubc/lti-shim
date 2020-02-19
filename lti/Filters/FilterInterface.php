<?php

namespace UBC\LTI\Filters;

interface FilterInterface
{
    public function filter(array $params): array;
}
