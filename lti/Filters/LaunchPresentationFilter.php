<?php

namespace UBC\LTI\Filters;

use App\Models\LtiSession;

use UBC\LTI\Filters\FilterInterface;
use UBC\LTI\Param;

class LaunchPresentationFilter implements FilterInterface
{
    public function filter(array $params, LtiSession $session): array
    {
        if (isset($params[Param::LAUNCH_PRESENTATION_URI])) {
            // launch presentation can pass other values, but we only want to
            // keep these param keys
            $keepParams = [
                'document_target' => 1,
                'height' => 2,
                'width' => 3
            ];
            $params[Param::LAUNCH_PRESENTATION_URI] = array_intersect_key(
                $params[Param::LAUNCH_PRESENTATION_URI], $keepParams);
        }
        return $params;
    }
}
