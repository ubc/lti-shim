<?php

namespace UBC\LTI\Specs\Launch\Filters;

use App\Models\LtiSession;
use App\Models\LtiFakeUser;

use UBC\LTI\Filters\AbstractFilter;
use UBC\LTI\Specs\Launch\Filters\FilterInterface;
use UBC\LTI\Utils\Param;

class UserFilter extends AbstractFilter implements FilterInterface
{
    protected const LOG_HEADER = 'User Filter';

    public function filter(array $params, LtiSession $session): array
    {
        $this->ltiLog->debug('Trying', $session);
        $fakeUser = LtiFakeUser::getByRealUser($session->course_context_id,
            $session->tool_id, $session->lti_real_user);
        if (isset($params[Param::LOGIN_HINT])) {
            $params[Param::LOGIN_HINT] = $fakeUser->login_hint;
        }
        if (isset($params[Param::SUB])) {
            $params[Param::SUB] = $fakeUser->sub;
        }
        if (isset($params[Param::NAME])) {
            $params[Param::NAME] = $fakeUser->name;
            $params[Param::GIVEN_NAME] = $fakeUser->first_name;
            $params[Param::FAMILY_NAME] = $fakeUser->last_name;
            $this->ltiLog->debug('User mapped', $session,
                $session->lti_real_user, $fakeUser);
        }
        if (isset($params[Param::EMAIL])) {
            $params[Param::EMAIL] = $fakeUser->email;
        }

        return $params;
    }
}
