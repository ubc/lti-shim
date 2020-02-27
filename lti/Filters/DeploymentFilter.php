<?php

namespace UBC\LTI\Filters;

use App\Models\LtiSession;
use App\Models\Deployment;

use UBC\LTI\Filters\FilterInterface;
use UBC\LTI\Param;

class DeploymentFilter implements FilterInterface
{
    public function filter(array $params, LtiSession $session): array
    {
        $deployment = Deployment::find($session->session['deployment_id']);
        if (!$deployment->fake_lti_deployment_id) $deployment->fillFakeFields();

        if (isset($params[Param::LTI_DEPLOYMENT_ID])) {
            $params[Param::LTI_DEPLOYMENT_ID] =
                $deployment->fake_lti_deployment_id;
        }
        if (isset($params[Param::DEPLOYMENT_ID_URI])) {
            $params[Param::DEPLOYMENT_ID_URI] =
                $deployment->fake_lti_deployment_id;
        }
        return $params;
    }
}
