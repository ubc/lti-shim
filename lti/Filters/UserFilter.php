<?php

namespace UBC\LTI\Filters;

use App\Models\LtiSession;
use App\Models\LtiFakeUser;

use UBC\LTI\Filters\FilterInterface;
use UBC\LTI\Param;

class UserFilter implements FilterInterface
{
    public function filter(array $params, LtiSession $session): array
    {
        $fakeUser = $this->getFakeUser($session);
        if (isset($params[Param::LOGIN_HINT])) {
            $params[Param::LOGIN_HINT] = $fakeUser->login_hint;
        }
        if (isset($params[Param::SUB])) {
            $params[Param::SUB] = $fakeUser->login_hint;
        }
        if (isset($params[Param::NAME])) {
            $params[Param::NAME] = $fakeUser->name;
        }
        if (isset($params[Param::EMAIL])) {
            $params[Param::EMAIL] = $fakeUser->email;
        }

        return $params;
    }

    private function getFakeUser(LtiSession $session): LtiFakeUser
    {
        $user = LtiFakeUser::firstWhere([
            'lti_real_user_id' => $session->lti_real_user_id,
            'tool_id' => $session->tool_id
        ]);
        if ($user) return $user; // user already exists
        // new user, need to create
        $user = new LtiFakeUser();
        $user->lti_real_user_id = $session->lti_real_user_id;
        $user->tool_id = $session->tool_id;
        $user->fillFakeFields();
        return $user;
    }
}
