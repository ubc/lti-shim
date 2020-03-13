<?php

namespace UBC\LTI\Filters;

use App\Models\LtiSession;
use App\Models\ResourceLink;

use UBC\LTI\Filters\FilterInterface;
use UBC\LTI\Param;

class ResourceLinkFilter implements FilterInterface
{
    public function filter(array $params, LtiSession $session): array
    {
        if (isset($params[Param::RESOURCE_LINK_URI])) {
            $realLinkId = $params[Param::RESOURCE_LINK_URI]['id'];
            $resourceLink = ResourceLink::firstOrCreate([
                'deployment_id' => $session->deployment_id,
                'real_link_id' => $realLinkId
            ]);
            if (!$resourceLink->fake_link_id) $resourceLink->fillFakeFields();
            $params[Param::RESOURCE_LINK_URI] = [
                'id' => $resourceLink->fake_link_id
            ];
        }
        return $params;
    }
}
