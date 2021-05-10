<?php

namespace UBC\LTI\Specs\Launch\Filters;

use Illuminate\Support\Facades\Log;

use App\Models\LtiSession;
use App\Models\Deployment;

use UBC\LTI\Filters\AbstractWhitelistFilter;
use UBC\LTI\Specs\Launch\Filters\FilterInterface;
use UBC\LTI\Specs\ParamChecker;
use UBC\LTI\Utils\Param;

// Assignment and Grades Service (AGS)
class DeepLinkFilter extends AbstractWhitelistFilter implements FilterInterface
{
    protected const LOG_HEADER = 'Dl Filter';

    protected array $whitelists = [Param::AGS_SCOPES];

    public function filter(array $params, LtiSession $session): array
    {
        if (
            $params[Param::MESSAGE_TYPE_URI] !=
            Param::MESSAGE_TYPE_DEEP_LINK_REQUEST
        ) {
            if (isset($params[Param::DL_CLAIM_URI])) {
                $this->ltiLog->warning(
                    'Regular launch contains Deep Link claim, dropping claim',
                    $session
                );
                unset($params[Param::DL_CLAIM_URI]);
            }
            $this->ltiLog->debug('Skipping', $session);
            return $params;
        }
        $this->ltiLog->debug('Running', $session);

        if (!isset($params[Param::DL_CLAIM_URI])) {
            $this->ltiLog->error(
                'Missing deep link settings in deep link launch', $session);
            return $params;
        }

        $settings = $params[Param::DL_CLAIM_URI];
        // make sure required claims are present
        $checker = new ParamChecker($settings, $this->ltiLog);
        $requiredParams = [
            Param::DL_RETURN_URL,
            Param::DL_ACCEPT_TYPES,
            Param::DL_ACCEPT_PRESENTATION_DOCUMENT_TARGETS
        ];
        $checker->requireParams($requiredParams);

        // TODO: deep link return needs to be mapped to the shim
        // TODO: opaque state in 'data' that needs to be preserved for pass
        // back to originating platform

        // set required claims
        $newSettings = [
            Param::DL_RETURN_URL => 'TODO', // TODO
            Param::DL_ACCEPT_TYPES => $settings[Param::DL_ACCEPT_TYPES],
            Param::DL_ACCEPT_PRESENTATION_DOCUMENT_TARGETS =>
                $settings[Param::DL_ACCEPT_PRESENTATION_DOCUMENT_TARGETS]
        ];
        // optional claims that can be passed through as is
        if (isset($settings[Param::DL_ACCEPT_MEDIA_TYPES]))
            $newSettings[Param::DL_ACCEPT_MEDIA_TYPES] =
                $settings[Param::DL_ACCEPT_MEDIA_TYPES];
        if (isset($settings[Param::DL_ACCEPT_MULTIPLE]))
            $newSettings[Param::DL_ACCEPT_MULTIPLE] =
                $settings[Param::DL_ACCEPT_MULTIPLE];
        if (isset($settings[Param::DL_AUTO_CREATE]))
            $newSettings[Param::DL_AUTO_CREATE] =
                $settings[Param::DL_AUTO_CREATE];
        if (isset($settings[Param::TITLE]))
            $newSettings[Param::TITLE] = $settings[Param::TITLE];
        if (isset($settings[Param::TEXT]))
            $newSettings[Param::TEXT] = $settings[Param::TEXT];
        // TODO: generate our own state 'data'

        $params[Param::DL_CLAIM_URI] = $newSettings;
        return $params;
    }
}
