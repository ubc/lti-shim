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
    private LtiSession $ltiSession;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->ltiSession = LtiSession::getSession($this->request);
        $this->ltiLog = new LtiLog('Launch (Midway)',
                                   $this->ltiSession->log_stream);
    }

    public function getArrivalResponse(): Response
    {
        $this->ltiLog->debug('Arrived from Tool Side', $this->request);

        $users = LtiFakeUser::getByCourseContext(
            $this->ltiSession->course_context_id,
            $this->ltiSession->tool_id
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
            'tool' => $this->ltiSession->tool->name,
            'platform' => $this->ltiSession->deployment->platform->name,
            'users' => $users
        ];

        $roleVo = new RoleVocabulary();
        if ($roleVo->canLookupRealUsers(
                $this->ltiSession->token[Param::ROLES_URI])
        ) {
            $this->ltiLog->debug('Access lookup tool', $this->request);
            return response()->view('lti/launch/midway/lookup', $response);
        }
        else {
            $this->ltiLog->debug('No lookup, continue', $this->request);
            return response()->view('lti/launch/midway/auto', $response);
        }
    }

    public function getDepartureResponse()
    {
        $this->ltiLog->debug('Depart to Platform Side', $this->request);
        return redirect()->action(
            'LTI\Launch\PlatformLaunchController@login',
            [
                Param::LTI_MESSAGE_HINT =>
                    $this->request->input(Param::LTI_MESSAGE_HINT)
            ]
        );
    }
}
