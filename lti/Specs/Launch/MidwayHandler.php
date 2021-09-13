<?php
namespace UBC\LTI\Specs\Launch;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use App\Models\LtiSession;
use App\Models\User;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Utils\Param;
use UBC\LTI\Specs\RoleVocabulary;

class MidwayHandler
{
    private LtiLog $ltiLog;
    private Request $request;
    private LtiSession $session;

    public function __construct(Request $request, LtiSession $session)
    {
        $this->request = $request;
        $this->session = $session;
        $this->ltiLog = new LtiLog('Launch (Midway)', $session->log_stream);
    }

    public function recv()
    {
        $this->ltiLog->debug('Recevied', $this->request);
        // midway only, so no need for any of the continuation params
        if ($this->session->is_midway_lookup_only) return;

        if (!$this->request->has(Param::MIDWAY_REDIRECT_URI)) {
            throw new LtiException($this->ltiLog->msg(
                'Invalid Midway request, missing redirect uri.'));
        }
    }

    public function send(): Response
    {
        // don't bother with user lookup if this is a deep link request
        if ($this->session->token[Param::MESSAGE_TYPE_URI] ==
            Param::MESSAGE_TYPE_DEEP_LINK_REQUEST) {
            return $this->getAutoSubmitResponse();
        }
        // check if this is the fake identity's first launch, if so, we need to
        // ask them to set anonymization options
        $fakeUser = $this->session->lti_fake_user;
        if ($fakeUser->enable_first_time_setup) {
            return $this->getFirstTimeSetupResponse();
        }

        // if user is an instructor, they should go to the user lookup first
        $role = new RoleVocabulary();
        $canLookupUsers =
            $role->canLookupRealUsers($this->session->token[Param::ROLES_URI]);
        if ($this->session->is_midway_lookup_only) {
            if ($canLookupUsers) return $this->getLookupResponse();
            abort(Response::HTTP_FORBIDDEN, 'Please use a regular launch');
        }
        if ($this->session->tool->enable_midway_lookup && $canLookupUsers) {
            return $this->getLookupResponse();
        }
        return $this->getAutoSubmitResponse();
    }

    /**
     * User needs to go to first time setup in order to select anonymization
     * options.
     */
    private function getFirstTimeSetupResponse(): Response
    {
        // generate a midway api access token that only allows them access
        // to the fake user identity
        $user = User::getMidwayApiUser();
        $token = $user->createToken(Str::random(20), [
            $user->getSelectAnonymizationAbility(
                $this->session->lti_fake_user->id)
        ]);

        $response = [
            Param::ID_TOKEN => $this->request->input(Param::ID_TOKEN),
            Param::MIDWAY_REDIRECT_URI =>
                            $this->request->input(Param::MIDWAY_REDIRECT_URI),
            'fakeUserId' => $this->session->lti_fake_user->id,
            'isMidwayOnly' => $this->session->is_midway_lookup_only,
            'token' => $token->plainTextToken,
            'toolName' => $this->session->tool->name,
        ];
        if ($this->request->has(Param::STATE)) {
            $response[Param::STATE] = $this->request->input(Param::STATE);
        }

        return response()->view('lti/launch/midway/first_time_setup',
                                $response);
    }

    /**
     * User is allowed to lookup real users, so we have to send them to the
     * midway user lookup tool.
     */
    private function getLookupResponse(): Response
    {
        $this->ltiLog->debug('Access lookup tool', $this->request);

        $courseContextId = $this->session->course_context_id;
        $toolId = $this->session->tool_id;
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
            'isMidwayOnly' => $this->session->is_midway_lookup_only,
            'platformName' => $this->session->deployment->platform->name,
            'toolId' => $toolId,
            'toolName' => $this->session->tool->name,
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
}
