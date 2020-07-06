<?php

namespace UBC\LTI\Specs\Launch\Filters;

use App\Models\LtiSession;
use App\Models\LtiFakeUser;

use UBC\LTI\Specs\Launch\Filters\FilterInterface;
use UBC\LTI\Param;

class UserFilter implements FilterInterface
{
    public function filter(array $params, LtiSession $session): array
    {
        $fakeUser = LtiFakeUser::getByRealUser($session->tool_id,
            $session->lti_real_user);
        if (isset($params[Param::LOGIN_HINT])) {
            $params[Param::LOGIN_HINT] = $fakeUser->login_hint;
        }
        if (isset($params[Param::SUB])) {
            $params[Param::SUB] = $fakeUser->sub;
        }
        if (isset($params[Param::NAME])) {
            $params[Param::NAME] = $fakeUser->name;
        }
        if (isset($params[Param::EMAIL])) {
            $params[Param::EMAIL] = $fakeUser->email;
        }

        return $params;
    }
}
