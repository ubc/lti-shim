<?php
namespace UBC\LTI\Specs\Launch;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Models\LtiFakeUser;
use App\Models\LtiSession;

use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Utils\Param;
use UBC\LTI\Specs\RoleVocabulary;

class MidwayLaunch
{
    private LtiLog $ltiLog;
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->ltiLog = new LtiLog('Launch (Midway)');
    }

    public function getArrivalResponse(): Response
    {
        $this->ltiLog->debug('Arrived from Tool Side', $this->request);
        $ltiSession = LtiSession::getSession($this->request);

        $users = LtiFakeUser::getByCourseContext(
            $ltiSession->course_context_id,
            $ltiSession->tool_id
        );

        // hide some fields we send out
        $users->makeHidden(['login_hint', 'sub', 'lti_real_user_id', 'tool_id',
            'course_context_id', 'created_at', 'updated_at']);
        foreach ($users as $user) {
            $user->lti_real_user->makeHidden(['login_hint', 'email', 'sub',
                'non_lti_id', 'platform_id', 'created_at', 'updated_at']);
        }

        $response = [
            Param::LTI_MESSAGE_HINT =>
                $this->request->input(Param::LTI_MESSAGE_HINT),
            'tool' => $ltiSession->tool->name,
            'platform' => $ltiSession->deployment->platform->name,
            'users' => $users
        ];

        $roleVo = new RoleVocabulary();
        if ($roleVo->canLookupRealUsers($ltiSession->token[Param::ROLES_URI])) {
            $this->ltiLog->debug('Access lookup tool', $this->request);
            return response()->view('lti/launch/midway/lookup', $response);
        }
        else {
            $this->ltiLog->debug('No lookup, continue', $this->request);
            return response()->view('lti/launch/midway/auto', $response);
        }
    }
}
