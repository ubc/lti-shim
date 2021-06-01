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
        $this->ltiSession = $this->getSession();
        $this->ltiLog = new LtiLog('Launch (Midway)',
                                   $this->ltiSession->log_stream);
    }

    public function getArrivalResponse(): Response
    {
        $this->ltiLog->debug('Arrived from Tool Side', $this->request);
        if (!$this->request->has(Param::MIDWAY_REDIRECT_URI)) {
            throw new LtiException($this->ltiLog->msg(
                'Invalid Midway request, missing redirect uri.'));
        }

        // don't bother with user lookup if this is a deep link request
        if ($this->ltiSession->token[Param::MESSAGE_TYPE_URI] ==
            Param::MESSAGE_TYPE_DEEP_LINK_REQUEST) {
            return $this->getAutoSubmitResponse();
        }

        $roleVo = new RoleVocabulary();
        if ($roleVo->canLookupRealUsers(
                $this->ltiSession->token[Param::ROLES_URI])
        ) {
            return $this->getLookupResponse();
        }
        return $this->getAutoSubmitResponse();
    }

    /**
     * User is allowed to lookup real users, so we have to send them to the
     * midway user lookup tool.
     */
    private function getLookupResponse(): Response
    {
        $this->ltiLog->debug('Access lookup tool', $this->request);

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
            Param::ID_TOKEN => $this->request->input(Param::ID_TOKEN),
            Param::MIDWAY_REDIRECT_URI =>
                            $this->request->input(Param::MIDWAY_REDIRECT_URI),
            'courseContextId' => $courseContextId,
            'platformName' => $this->ltiSession->deployment->platform->name,
            'toolId' => $toolId,
            'toolName' => $this->ltiSession->tool->name,
            'token' => $token->plainTextToken
        ];
        if ($this->request->has(Param::STATE)) {
            $response[Param::STATE] = $this->request->input(Param::STATE);
        }

        return response()->view('lti/launch/midway/lookup', $response);
    }

    /**
     * User does not have access to lookup real users, so just continue on
     * with sending the auth resp to the target tool.
     */
    private function getAutoSubmitResponse(): Response
    {
        $this->ltiLog->debug('No lookup, continue', $this->request);
        $autoParams = [
            Param::ID_TOKEN => $this->request->input(Param::ID_TOKEN) ];
        if ($this->request->has(Param::STATE))
            $autoParams[Param::STATE] = $this->request->input(Param::STATE);

        return response()->view(
            'lti/launch/auto_submit_form',
            [
                'title' => 'Auth Response After Midway',
                'formUrl' => $this->request->input(Param::MIDWAY_REDIRECT_URI),
                'params' => $autoParams
            ]
        );
    }

    /**
     * Get the LtiSession from the session param.
     */
    private function getSession(): LtiSession
    {
        if (!$this->request->has(Param::MIDWAY_SESSION))
            throw new LtiException('No LTI session was passed to Midway.');
        return LtiSession::decodeEncryptedId(
                                    $this->request->get(Param::MIDWAY_SESSION));
    }
}
