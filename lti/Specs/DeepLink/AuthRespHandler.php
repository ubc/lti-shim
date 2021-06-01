<?php
namespace UBC\LTI\Specs\DeepLink;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Jose\Easy\Build;
use Jose\Easy\JWT;
use Jose\Easy\Load;

use App\Models\CourseContext;
use App\Models\Deployment;
use App\Models\LtiFakeUser;
use App\Models\LtiRealUser;
use App\Models\LtiSession;
use App\Models\Platform;

use UBC\LTI\Specs\JwsUtil;
use UBC\LTI\Specs\Launch\Filters\AgsFilter;
use UBC\LTI\Specs\Launch\Filters\CourseContextFilter;
use UBC\LTI\Specs\Launch\Filters\DeepLinkFilter;
use UBC\LTI\Specs\Launch\Filters\DeploymentFilter;
use UBC\LTI\Specs\Launch\Filters\GradebookMessageFilter;
use UBC\LTI\Specs\Launch\Filters\LaunchPresentationFilter;
use UBC\LTI\Specs\Launch\Filters\NrpsFilter;
use UBC\LTI\Specs\Launch\Filters\UserFilter;
use UBC\LTI\Specs\Launch\Filters\WhitelistFilter;
use UBC\LTI\Specs\ParamChecker;
use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Utils\Param;
use UBC\LTI\Utils\UriUtil;

/**
 * SECOND STAGE of LTI launch, the Authorization Request
 *
 * We first receive an auth req from the target tool.
 * We restore state from LtiSession.
 * We then send an auth req to the originating platform.
 */
class AuthRespHandler
{
    private LtiLog $ltiLog;
    private LtiSession $session;
    private ParamChecker $checker;
    private Request $request;

    private array $filters;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->ltiLog = new LtiLog('Launch (Auth Resp)');
        $this->checker = new ParamChecker($request->input(), $this->ltiLog);
        $this->loadSession();
        $this->filters = [
            new DeploymentFilter($this->ltiLog),
            new WhitelistFilter($this->ltiLog),
            new UserFilter($this->ltiLog),
            new LaunchPresentationFilter($this->ltiLog),
            new CourseContextFilter($this->ltiLog),
            new NrpsFilter($this->ltiLog),
            new AgsFilter($this->ltiLog),
            new GradebookMessageFilter($this->ltiLog),
            new DeepLinkFilter($this->ltiLog)
        ];
    }

    /**
     * Returns the OIDC login parameters we should send to the target tool. This
     * is the shim acting as a platform.
     */
    public function sendAuth(): Response
    {
        $gotJwt = $this->receiveAuth();
        $this->ltiLog->info('Platform Side, send auth resp.', $this->request);

        // check to make sure that we have a valid session
        if (!isset($this->session->token[Param::REDIRECT_URI]) ||
            !isset($this->session->token[Param::NONCE]))
            throw new LtiException($this->ltiLog->msg(
                'Invalid session, missing previous steps', $this->request));

        $authRespUri = $this->session->token[Param::REDIRECT_URI];
        $nonce = $this->session->token[Param::NONCE];

        $authRespParams = [];
        // set state if target tool sent us one
        if (isset($this->session->token[Param::STATE]))
            $authRespParams[Param::STATE] = $this->session->token[Param::STATE];

        // NOTE: this will remove values stored from previous steps
        // I don't want to rewrite all the filters which expect that the
        // id_token payload is stored in LtiSession's token
        $this->updateSession($gotJwt);

        // set payload for id_token
        $time = time();
        $payload = [
            Param::ISS => config('lti.iss'),
            Param::SUB => $gotJwt->claims->get(Param::SUB), // user id
            Param::AUD => $this->session->tool->client_id,
            Param::EXP => $time + Param::EXP_TIME, // expires 1 hour
            Param::IAT => $time, // issued at
            Param::NBF => $time, // not before
            Param::NONCE => $nonce,
            Param::MESSAGE_TYPE_URI =>
                                  $gotJwt->claims->get(Param::MESSAGE_TYPE_URI),
            Param::ROLES_URI => $gotJwt->claims->get(Param::ROLES_URI),
            Param::VERSION_URI => '1.3.0',
            Param::DEPLOYMENT_ID_URI =>
                                  $this->session->deployment->lti_deployment_id,
            Param::TARGET_LINK_URI_URI => $this->session->tool->target_link_uri,
            Param::RESOURCE_LINK_URI =>
                                  $gotJwt->claims->get(Param::RESOURCE_LINK_URI)
        ];
        // pass through optional params if they exist
        foreach (
            array_keys(WhitelistFilter::ID_TOKEN_OPTIONAL_CLAIMS)
            as
            $optParam
        ) {
            $this->ltiLog->debug('Including optional param: ' . $optParam);
            if ($gotJwt->claims->has($optParam))
                $payload[$optParam] = $gotJwt->claims->get($optParam);
        }
        // filter payload
        $this->ltiLog->debug('Pre-filter id_token: ' . json_encode($payload),
            $this->request, $this->session);
        $payload = $this->applyFilters($payload);
        $this->ltiLog->debug('Post-filter id_token: ' . json_encode($payload),
            $this->request, $this->session);
        // encode into jwt
        $key = Platform::getOwnPlatform()->getKey();
        $this->ltiLog->debug('id_token: key: '. $key->id .' kid: ' . $key->kid,
            $this->request, $this->session);
        $authRespParams[Param::ID_TOKEN] = Build::jws()
            ->typ(Param::JWT)
            ->alg(Param::RS256)
            ->header(Param::KID, $key->kid)
            ->payload($payload)
            ->sign($key->key);

        // params for midway where users interact with the shim (if any)
        $authRespParams[Param::MIDWAY_REDIRECT_URI] = $authRespUri;
        $authRespParams[Param::MIDWAY_SESSION] =
                                            $this->session->createEncryptedId();

        $fakeUser = LtiFakeUser::getByRealUser(
            $this->session->course_context_id,
            $this->session->tool_id,
            $this->session->lti_real_user
        );
        $this->ltiLog->notice('Launch completed', $this->request,
            $this->session, $this->session->course_context,
            $this->session->lti_real_user, $fakeUser
        );

        return response()->view(
            'lti/launch/auto_submit_form',
            [
                'title' => 'Auth Response to Midway',
                'formUrl' => route('lti.launch.midway'),
                'params' => $authRespParams
            ]
        );
    }

    /**
     * Just validates to see if the OIDC login request we received from the
     * platform was valid. This is the shim acting as a tool.
     */
    private function receiveAuth(): JWT
    {
        $this->ltiLog->info('Tool Side, recv auth resp: ' .
            json_encode($this->request->input()), $this->request);

        // required params that needs to be present
        $requiredParams = [
            //Param::STATE, // should be checked by loadSession() already
            Param::ID_TOKEN,
        ];
        $this->checker->requireParams($requiredParams);

        // Verify the id_token JWT's signature and unpack it into a JWT object
        $jwsUtil = new JwsUtil($this->request->input(Param::ID_TOKEN),
                               $this->ltiLog);
        $jwt = $jwsUtil->verifyAndDecode(
            $this->session->platform_client->platform,
            $this->session->platform_client->client_id,
            $this->session->platform_client->platform->iss
        );

        $this->checkTokenParams($jwt);

        return $jwt;
    }

    /**
     * Apply each filter to our id_token payload
     */
    private function applyFilters(array $params): array
    {
        $this->ltiLog->debug("Applying Filters", $this->request,
            $this->session);
        foreach ($this->filters as $filter) {
            $params = $filter->filter($params, $this->session);
        }
        return $params;
    }

    /**
     * Make sure that the id_token comes with all the required parameters
     * according to spec.
     */
    private function checkTokenParams(JWT $jwt)
    {
        // check if message type is supported
        $messageType = $jwt->claims->get(Param::MESSAGE_TYPE_URI);
        if (!in_array($messageType, Param::MESSAGE_TYPES)) {
            throw new LtiException($this->ltiLog->msg(
                'Unsupported Message Type: ' . $messageType,
                $this->request));
        }

        // check for required params in token
        $checker = new ParamChecker($jwt->claims->all(), $this->ltiLog);

        $requiredValues = [ Param::VERSION_URI => Param::VERSION_130 ];
        // if oidc login contains lti_deployment_id, we need to check that it
        // matches the one given in the id_token
        if (isset($this->session->token[Param::LTI_DEPLOYMENT_ID])) {
            $requiredValues[Param::DEPLOYMENT_ID_URI] =
                $this->session->token[Param::LTI_DEPLOYMENT_ID];
        }
        $checker->requireValues($requiredValues);

        $checker->requireParams([
            Param::TARGET_LINK_URI_URI,
            Param::RESOURCE_LINK_URI,
            Param::ROLES_URI,
            Param::NONCE,
            Param::MESSAGE_TYPE_URI
        ]);

        // verify that the target_link_uri points to the shim
        $target = $jwt->claims->get(Param::TARGET_LINK_URI_URI);
        if (!UriUtil::isSameSite(config('lti.iss'), $target))
            throw new LtiException($this->ltiLog->msg(
                "target_link_uri is some other site: $target", $this->request));
    }

    /**
     * Load the LtiSession from the state param.
     */
    private function loadSession()
    {
        if (!$this->request->has(Param::STATE))
            throw new LtiException($this->ltiLog->msg(
                'Missing state in auth response', $this->request));

        $this->session = LtiSession::decodeEncryptedId(
            $this->request->input(Param::STATE));
        $this->ltiLog->setStreamid($this->session->log_stream);
    }

    /**
     * Update the LtiSession based on new data in the id_token.
     * This would be:
     * - course_context_id
     * - deployment_id
     * - lti_real_user_id
     *
     * Updating session is needed since the filters need access to that info.
     * This also removes all data stored by previous steps, since it gets
     * replaced by id_token's payload.
     */
    private function updateSession(JWT $jwt)
    {
        $platformId = $this->session->platform_client->platform_id;
        $user = LtiRealUser::getFromLaunch($platformId, $jwt->claims->all());
        $deployment = Deployment::firstOrCreate([
            'lti_deployment_id' => $jwt->claims->get(Param::DEPLOYMENT_ID_URI),
            'platform_id'       => $platformId
        ]);
        $courseId = CourseContextFilter::getContextId($jwt->claims->all());
        $courseContext = CourseContext::createOrGet(
            $deployment->id,
            $this->session->tool_id,
            $courseId
        );

        $this->session->course_context_id = $courseContext->id;
        $this->session->deployment_id = $deployment->id;
        $this->session->lti_real_user_id = $user->id;
        $this->session->token = $jwt->claims->all(); // replace previous data
        $this->session->save();
    }
}
