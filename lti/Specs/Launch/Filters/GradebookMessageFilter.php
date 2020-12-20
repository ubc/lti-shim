<?php

namespace UBC\LTI\Specs\Launch\Filters;

use Illuminate\Support\Facades\Log;

use App\Models\LtiRealUser;
use App\Models\LtiFakeUser;
use App\Models\LtiSession;

use UBC\LTI\Filters\AbstractFilter;
use UBC\LTI\Specs\Launch\Filters\FilterInterface;
use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\Param;

class GradebookMessageFilter extends AbstractFilter implements FilterInterface
{
    protected const LOG_HEADER = 'Gradebook Message Filter';

    public function filter(array $params, LtiSession $session): array
    {
        $this->ltiLog->debug('Start');
        if (isset($params[Param::FOR_USER_URI])) {
            $this->ltiLog->debug('Filtering "for user" claim', $session);
            $forUser = $params[Param::FOR_USER_URI];
            if (!isset($forUser[Param::USER_ID])) {
                throw new LtiException(
                    $this->ltiLog->msg('Missing user_id in for user claim'));
            }
            $realUser = LtiRealUser::getBySub($session->deployment->platform_id,
                                              $forUser[Param::USER_ID]);
            if (!$realUser) {
                throw new LtiException($this->ltiLog->msg(
                    'Unknown user_id in for user claim: ' .
                    $forUser[Param::USER_ID]));
            }
            $fakeUser = LtiFakeUser::getByRealUser($session->course_context_id,
                $session->tool_id, $realUser);
            $params[Param::FOR_USER_URI] = [
                Param::USER_ID => $fakeUser->sub,
                Param::NAME => $fakeUser->name,
                Param::EMAIL => $fakeUser->email
            ];
        }
        $this->ltiLog->debug('Done');
        return $params;
    }
}
