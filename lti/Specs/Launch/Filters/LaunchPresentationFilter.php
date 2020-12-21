<?php
namespace UBC\LTI\Specs\Launch\Filters;

use Illuminate\Support\Facades\Log;

use App\Models\LtiSession;
use App\Models\ReturnUrl;

use UBC\LTI\Filters\AbstractFilter;
use UBC\LTI\Specs\Launch\Filters\FilterInterface;
use UBC\LTI\Utils\Param;

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
        $presentationClaim = $params[Param::LAUNCH_PRESENTATION_URI];
        if (isset($presentationClaim[Param::RETURN_URL])) {
            $this->ltiLog->debug('Has Return Url', $session);
            $returnUrl = ReturnUrl::createOrGet(
                $presentationClaim[Param::RETURN_URL],
                $session->course_context_id,
                $session->deployment_id,
                $session->tool_id
            );
            $presentationClaim[Param::RETURN_URL] = $returnUrl->getShimUrl();
        }
        // launch presentation can pass other values, but we only want to
        // keep these param keys
        $keepParams = [
            Param::DOCUMENT_TARGET => 1,
            Param::HEIGHT => 2,
            Param::WIDTH => 3,
            Param::RETURN_URL => 4,
            Param::LOCALE => 5
        ];
        $presentationClaim = array_intersect_key($presentationClaim,
                                                 $keepParams);
        $params[Param::LAUNCH_PRESENTATION_URI] = $presentationClaim;
        return $params;
    }
}
