<?php

namespace UBC\LTI\Specs\Launch\Filters;

use Illuminate\Support\Facades\Log;

use App\Models\LtiSession;
use App\Models\LtiFakeUser;

use UBC\LTI\Filters\AbstractFilter;
use UBC\LTI\Specs\Launch\Filters\FilterInterface;
use UBC\LTI\Specs\ParamChecker;
use UBC\LTI\Utils\Param;

// Piazza Filter - Piazza requires some custom params that we need to filter.
class PiazzaFilter extends AbstractFilter implements FilterInterface
{
    private const PIAZZA = 'piazza';
    private const PARAM_USER_ID = 'user_id';
    private const PARAM_LOGON_ID = 'logon_id';
    private const PARAM_COURSE_ID = 'course_id';
    
    protected const LOG_HEADER = 'Piazza Filter';

    public function filter(array $params, LtiSession $session): array
    {
        $toolName = strtolower($session->tool->name);
        // TODO: make sure CUSTOM_URI doesn't get passed in non-piazza launches
        if (strpos($toolName, self::PIAZZA) === false) {
            $this->ltiLog->debug('Skipping, not Piazza', $session);
            return $params;
        }
        $this->ltiLog->debug('Running, Piazza detected', $session);

        if (!isset($params[Param::CUSTOM_URI])) {
            $this->ltiLog->warning(
                'No custom claims to filter, is Piazza configured correctly?',
                $session
            );
            return $params;
        }

        $customParams = $params[Param::CUSTOM_URI];
        $realUserId = $customParams[self::PARAM_USER_ID] ?? '';
        $realLogonId = $customParams[self::PARAM_LOGON_ID] ?? '';
        $realCourseId = $customParams[self::PARAM_COURSE_ID] ?? '';
        if (!($realUserId && $realLogonId && $realCourseId)) {
            $this->ltiLog->error(
                'Missing Piazza config value in Piazza launch', $session);
            unset($params[Param::CUSTOM_URI]);
            return $params;
        }
        // NOTE: this requires UserFilter and CourseContextFilter to have run
        // before us
        $fakeUser = LtiFakeUser::getByRealUser($session->course_context_id,
            $session->tool_id, $session->lti_real_user);
        $params[Param::CUSTOM_URI] = [
            self::PARAM_USER_ID => $fakeUser->id,
            self::PARAM_LOGON_ID => $fakeUser->sub,
            self::PARAM_COURSE_ID => $session->course_context->id
        ];

        $this->ltiLog->debug('Done', $session);

        return $params;
    }

}
