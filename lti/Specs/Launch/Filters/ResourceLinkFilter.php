<?php

namespace UBC\LTI\Specs\Launch\Filters;

use App\Models\LtiSession;
use App\Models\ResourceLink;

use UBC\LTI\Filters\AbstractFilter;
use UBC\LTI\Specs\Launch\Filters\FilterInterface;
use UBC\LTI\Utils\Param;

class ResourceLinkFilter extends AbstractFilter implements FilterInterface
{
    protected const LOG_HEADER = 'Resource Link Filter';

    public function filter(array $params, LtiSession $session): array
    {
        if (!isset($params[Param::RESOURCE_LINK_URI])) {
            $this->ltiLog->debug('Skipping', $session);
            return $params;
        }
        $this->ltiLog->debug('Running', $session);
        $realLinkId = $params[Param::RESOURCE_LINK_URI]['id'];
        $resourceLink = ResourceLink::firstOrCreate([
            'deployment_id' => $session->deployment_id,
            'real_link_id' => $realLinkId
        ]);
        $this->ltiLog->debug('Resource Link: ' . $resourceLink->id, $session);
        if (!$resourceLink->fake_link_id) $resourceLink->fillFakeFields();
        $params[Param::RESOURCE_LINK_URI] = [
            'id' => $resourceLink->fake_link_id
        ];
        return $params;
    }
}
