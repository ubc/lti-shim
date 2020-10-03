<?php

namespace UBC\LTI\Specs\Launch\Filters;

use App\Models\LtiSession;
use App\Models\Deployment;

use UBC\LTI\Filters\AbstractFilter;
use UBC\LTI\Specs\Launch\Filters\FilterInterface;
use UBC\LTI\Utils\Param;

class DeploymentFilter extends AbstractFilter implements FilterInterface
{
    protected const LOG_HEADER = 'Deployment Filter';

    public function filter(array $params, LtiSession $session): array
    {
        $this->ltiLog->debug('Trying', $session);
        $deployment = $session->deployment;
        if (!$deployment->fake_lti_deployment_id) $deployment->fillFakeFields();

        if (isset($params[Param::LTI_DEPLOYMENT_ID])) {
            $this->ltiLog->debug('Replacing deployment ID', $session);
            $params[Param::LTI_DEPLOYMENT_ID] =
                $deployment->fake_lti_deployment_id;
        }
        if (isset($params[Param::DEPLOYMENT_ID_URI])) {
            $this->ltiLog->debug('Replacing deployment ID uri', $session);
            $params[Param::DEPLOYMENT_ID_URI] =
                $deployment->fake_lti_deployment_id;
        }
        return $params;
    }
}
