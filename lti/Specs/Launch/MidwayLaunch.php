<?php
namespace UBC\LTI\Specs\Launch;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use App\Models\LtiFakeUser;
use App\Models\LtiSession;
use App\Models\User;

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

        $courseContextId = $this->ltiSession->course_context_id;
        $toolId = $this->ltiSession->tool_id;
        // generate a midway api access token, storing the course context and
        // tool of the launch in as a token ability. This let us use token
        // ability later on to make sure that tokens can only access the course
        // context and tool they were generated in
        $user = User::getMidwayApiUser();
        $token = $user->createToken(Str::random(20),
            [$user->getLookupAbility($courseContextId, $toolId)]);

        $response = [
            Param::LTI_MESSAGE_HINT =>
                $this->request->input(Param::LTI_MESSAGE_HINT),
            'courseContextId' => $courseContextId,
            'platformName' => $this->ltiSession->deployment->platform->name,
            'toolId' => $toolId,
            'toolName' => $this->ltiSession->tool->name,
            'token' => $token->plainTextToken
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
