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
        $user = $this->getUser($session);
        if (isset($params[Param::LOGIN_HINT])) {
            $params[Param::LOGIN_HINT] = $user->fake_login_hint;
        }
        if (isset($params[Param::SUB])) {
            $params[Param::SUB] = $user->fake_login_hint;
        }
        if (isset($params[Param::NAME])) {
            $params[Param::NAME] = $user->fake_name;
        }
        if (isset($params[Param::EMAIL])) {
            $params[Param::EMAIL] = $user->fake_email;
        }

        return $params;
    }

    private function getUser(LtiSession $session): LtiUser
    {
        $user = LtiUser::firstWhere([
            'real_login_hint' => $session->session[Param::LOGIN_HINT],
            'deployment_id' => $session->session['deployment_id']
        ]);
        if ($user) return $user; // user already exists
        // new user, need to create
        $user = new LtiUser();
        $user->real_login_hint = $session->session[Param::LOGIN_HINT];
        $user->sub = $session->session[Param::SUB];
        $user->deployment_id = $session->session['deployment_id'];
        if (isset($session->session[Param::NAME])) {
            $user->real_name = $session->session[Param::NAME];
        }
        if (isset($session->session[Param::EMAIL])) {
            $user->real_email = $session->session[Param::EMAIL];
        }
        $user->fillFakeFields();
        return $user;
    }
}
