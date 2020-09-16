<?php

namespace UBC\LTI\Specs\Launch\Filters;

use App\Models\LtiSession;

use UBC\LTI\Filters\AbstractFilter;
use UBC\LTI\Specs\Launch\Filters\FilterInterface;
use UBC\LTI\Param;

class LaunchPresentationFilter extends AbstractFilter implements FilterInterface
{
    protected const LOG_HEADER = 'Launch Presentation Filter';

    public function filter(array $params, LtiSession $session): array
    {
        if (!isset($params[Param::LAUNCH_PRESENTATION_URI])) {
            $this->ltiLog->debug('Skipping', $session);
            return $params;
        }
        $this->ltiLog->debug('Running', $session);
        // launch presentation can pass other values, but we only want to
        // keep these param keys
        $keepParams = [
            'document_target' => 1,
            'height' => 2,
            'width' => 3
        ];
        $params[Param::LAUNCH_PRESENTATION_URI] = array_intersect_key(
            $params[Param::LAUNCH_PRESENTATION_URI], $keepParams);
        return $params;
    }
}
