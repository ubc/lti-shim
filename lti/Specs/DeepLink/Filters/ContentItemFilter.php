<?php

namespace UBC\LTI\Specs\DeepLink\Filters;

use Illuminate\Support\Facades\Log;

use App\Models\LtiSession;
use App\Models\DeepLink;
use App\Models\DeepLinkContentItem;
use App\Models\Deployment;

use UBC\LTI\Filters\AbstractFilter;
use UBC\LTI\Specs\DeepLink\Filters\FilterInterface;
use UBC\LTI\Specs\ParamChecker;
use UBC\LTI\Utils\Param;

// Deep Linking Content Item
class ContentItemFilter extends AbstractFilter implements FilterInterface
{
    protected const LOG_HEADER = 'Dl ContentItem Filter';

    protected array $whitelists = [Param::AGS_SCOPES];

    public function filter(array $params, DeepLink $dl): array
    {
        // do nothing if no content items
        if (!isset($params[Param::DL_CONTENT_ITEMS_URI])) {
            $this->ltiLog->log('Skipping', $dl);
            return $params;
        }
        $this->ltiLog->debug('Running', $dl);

        $contentItems = $params[Param::DL_CONTENT_ITEMS_URI];
        foreach ($contentItems as $key => $contentItem) {
            // required param 'type' tells us what type of content it is
            if (!isset($contentItem[Param::TYPE])) {
                $this->ltiLog->error('Content item missing required param type',
                                     $dl);
                continue;
            }
            // we only need to filter ltiResourceLink content
            if (!$contentItem[Param::TYPE] ==
                 Param::DL_CONTENT_TYPE_LTI_RESOURCE_LINK) {
                 $this->ltiLog->log('Skipping non-lti link content', $dl);
                 continue;
            }
            // need to store the original launch url
            $dlContentItem = DeepLinkContentItem::createOrGet(
                $contentItem[Param::URL], $dl->deployment_id, $dl->tool_id);
            // change original launch url to shim launch url
            $contentItems[$key][Param::URL] = $dlContentItem->shim_launch_url;
        }
        $params[Param::DL_CONTENT_ITEMS_URI] = $contentItems;

        return $params;
    }
}
