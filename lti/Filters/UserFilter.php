<?php

namespace UBC\LTI\Filters;

use App\Models\LtiSession;
use App\Models\LtiUser;

use UBC\LTI\Filters\FilterInterface;
use UBC\LTI\Param;

class UserFilter implements FilterInterface
{
    public function filter(array $params, LtiSession $session): array
    {
        $user;
        if (isset($params[Param::LOGIN_HINT])) {
            $user = LtiUser::where([
                'real_login_hint' => $params[Param::LOGIN_HINT],
                'deployment_id' => $session->session['deployment_id']
            ])->firstOrFail();
            $params[Param::LOGIN_HINT] = $user->fake_login_hint;
        }
        elseif (isset($params[Param::SUB])) {
            $user = LtiUser::where([
                'sub' => $params[Param::SUB],
                'deployment_id' => $session->session['deployment_id']
            ])->firstOrFail();
            $params[Param::SUB] = $user->fake_login_hint;
        }
        else {
            return $params; // no lti user to modify
        }

        if (isset($params[Param::NAME])) {
            $params[Param::NAME] = $user->fake_name;
        }
        if (isset($params[Param::EMAIL])) {
            $params[Param::EMAIL] = $user->fake_email;
        }

        return $params;
    }
}
