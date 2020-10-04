<?php
namespace UBC\LTI\Specs\Launch;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use Jose\Component\Checker\InvalidClaimException;
use Jose\Easy\Build;
use Jose\Easy\JWT;
use Jose\Easy\Load;

use App\Models\CourseContext;
use App\Models\Deployment;
use App\Models\LtiSession;
use App\Models\LtiRealUser;
use App\Models\Platform;
use App\Models\PlatformClient;
use App\Models\Tool;

use UBC\LTI\Utils\EncryptedState;
use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Utils\Param;
use UBC\LTI\Specs\JwsUtil;
use UBC\LTI\Specs\Launch\Filters\CourseContextFilter;
use UBC\LTI\Specs\ParamChecker;
use UBC\LTI\Specs\Security\Nonce;

// we're acting as the Tool
// the main idea is that we supply this object with the params that we receive
// and get the appropriate response params back
class ToolLaunch
{
    public const PLATFORM_CLIENT_ID_PARAM = 'platform_client_id';

    private const STREAM_ID = 'stream';

    private LtiLog $ltiLog;
    private Request $request; // laravel request object
    private ParamChecker $checker;

    private bool $hasLogin = false; // true if checkLogin() passed

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->ltiLog = new LtiLog('Launch (Tool Side)');
        $this->checker = new ParamChecker($request->input(), $this->ltiLog);
    }

    // first stage of the LTI launch on the tool side, we need to check that
    // the platform has sent us all the information we need
    public function checkLogin()
    {
        $this->ltiLog->info('Launch started: ' .
            json_encode($this->request->input()), $this->request);
        $requiredParams = [
            Param::ISS,
            Param::LOGIN_HINT,
            Param::TARGET_LINK_URI
        ];
        $this->checker->requireParams($requiredParams);

        // check that the request is coming from a platform we know
        $iss = $this->request->input(Param::ISS);
        $platform = Platform::getByIss($iss);
        if (!$platform)
            throw new LtiException($this->ltiLog->msg(
                "Unknown platform iss: $iss", $this->request));
        // make sure that target_link_uri is pointing to us
        $target = $this->request->input(Param::TARGET_LINK_URI);
        if (strpos($target, config('app.url')) !== 0)
            throw new LtiException($this->ltiLog->msg(
                "target_link_uri is some other site: $target", $this->request));

        $this->hasLogin = true;
    }

    // second stage of LTI launch on the tool side, we need to send an auth
    // request back to the platform, this function returns the params that
    // should be sent
    public function getLoginResponse(): array
    {
        $this->ltiLog->debug('Send Auth Request', $this->request);
        $ownTool = Tool::getOwnTool();
        // cannot generate the login response if we don't have a valid login
        if (!$this->hasLogin) $this->checkLogin();
        $resp = [
            Param::SCOPE => Param::OPENID,
            Param::RESPONSE_TYPE => Param::ID_TOKEN,
            Param::LOGIN_HINT => $this->request->input(Param::LOGIN_HINT),
            Param::RESPONSE_MODE => Param::FORM_POST,
            Param::PROMPT => Param::NONE,
            Param::REDIRECT_URI => $ownTool->auth_resp_url
        ];

        $iss = $this->request->input(Param::ISS);
        $platform = Platform::getByIss($iss);

        // Get the client_id for the response. To do that, we need to figure
        // out what tool is being targetted so we can get that tool's client_id
        // on the given platform. There's two ways to do this:
        // 1. get the target tool by the optional OIDC param client_id, if given
        // 2. get the target tool by unique target_link_uri
        $clientId = '';
        $platformClient = null;
        if ($this->request->filled(Param::CLIENT_ID)) {
            $platformClient = $platform->getPlatformClient(
                $this->request->input(Param::CLIENT_ID));
        }
        else {
            $targetLinkUri = $this->request->input(Param::TARGET_LINK_URI);
            $targetTool = Tool::getByTargetLinkUri($targetLinkUri);
            if (!$targetTool) throw new LtiException(
                $this->ltiLog->msg('Unknown target tool', $this->request));
            $platformClient = $targetTool->getPlatformClient($platform->id);
        }
        if (!$platformClient) throw new LtiException($this->ltiLog->msg(
            'Unregistered client', $this->request));
        $resp[Param::CLIENT_ID] = $platformClient->client_id;

        // lti_message_hint needs to be passed back as is to the platform
        if ($this->request->filled(Param::LTI_MESSAGE_HINT)) {
            $resp[Param::LTI_MESSAGE_HINT] =
                $this->request->input(Param::LTI_MESSAGE_HINT);
        }

        // storing values into state reduces the amount of bookkeeping we need
        // to do on our side, so I'm putting values that requires verification
        // against the id_token into the state.
        $resp[Param::STATE] = $this->createState($platformClient->id);
        $this->ltiLog->debug('Generated state: ' .  $resp[Param::STATE],
            $this->request);

        $resp[Param::NONCE] = Nonce::create();

        return ['response' => $resp, 'auth_req_url' => $platform->auth_req_url];
    }

    // third stage of the LTI launch on the tool side, we need to check the
    // authentication response sent back by the platform
    public function processAuth()
    {
        // stream id is stored in state, since we haven't processed state, it's
        // not available yet
        $this->ltiLog->setStreamId('Unavailable');
        $this->ltiLog->debug('Receive Auth Response', $this->request);
        $requiredParams = [
            Param::STATE,
            Param::ID_TOKEN
        ];
        $this->checker->requireParams($requiredParams);
        $this->ltiLog->debug('Received state: ' .
            $this->request->input(Param::STATE), $this->request);
        $this->ltiLog->debug('Received id_token: ' .
            $this->request->input(Param::ID_TOKEN), $this->request);
        $state = $this->processState($this->request->input(Param::STATE));
        $platformClient = PlatformClient::find(
            $state->claims->get(self::PLATFORM_CLIENT_ID_PARAM));
        $platform = $platformClient->platform;
        $tool = $platformClient->tool;

        $idToken = $this->processIdToken($this->request->input(Param::ID_TOKEN),
                                         $state, $platformClient);
        $deployment = Deployment::firstOrCreate(
            [
                'lti_deployment_id' => $idToken->claims
                                               ->get(Param::DEPLOYMENT_ID_URI),
                'platform_id'       => $platform->id
            ]
        );
        $user = LtiRealUser::getFromLaunch(
            $platform->id,
            $state->claims->get(Param::LOGIN_HINT),
            $idToken->claims->all()
        );
        $courseId = CourseContextFilter::getContextId($idToken->claims->all());
        $courseContext = CourseContext::createOrGet(
            $deployment->id,
            $tool->id,
            $courseId
        );
        // persist the session in the database
        $ltiSession = new LtiSession();
        $ltiSession->deployment_id = $deployment->id;
        $ltiSession->tool_id = $tool->id;
        $ltiSession->lti_real_user_id = $user->id;
        $ltiSession->course_context_id = $courseContext->id;
        $ltiSession->token = $idToken->claims->all();
        $ltiSession->log_stream = $this->ltiLog->getStreamId();
        $ltiSession->save();
        $this->ltiLog->debug('Auth Resp: ' . 'context: ' . $courseContext->id .
            ' real user: ' . $user->id . ' session: ' . $ltiSession->id,
            $this->request);
        // generate the session token to be passed on to the shim's tool side
        $state = EncryptedState::encrypt([
            'lti_session' => $ltiSession->id
        ]);

        return $state;
    }

    // verify the signature & params in the id_token
    private function processIdToken(
        string $token,
        JWT $state,
        PlatformClient $platformClient
    ): JWT {
        $jwt;
        $kid = '';
        try {
            $this->ltiLog->debug('Decode id_token', $this->request);
            $jwt = Load::jws($token);
            $jwsUtil = new JwsUtil($token, $this->ltiLog);
            $kid = $jwsUtil->getKid();
            $this->ltiLog->debug('id_token: kid: ' . $kid, $this->request);
        } catch(InvalidArgumentException $e) {
            throw new LtiException(
                $this->ltiLog->msg('invalid id_token',$this->request,$e),0,$e);
        }
        $jwk = $platformClient->platform->getKey($kid);
        $this->ltiLog->debug('id_token: key: ' . $jwk->id, $this->request);
        $jwt = $jwt->algs([Param::RS256]) // The algorithms allowed to be used
                   ->aud($platformClient->client_id)
                   ->iss($platformClient->platform->iss)
                   ->key($jwk->public_key); // Key used to verify the signature
        try {
            // check signature
            $jwt = $jwt->run();
            JwsUtil::verifyTimestamps($jwt, $this->ltiLog);
            // check required claim values
            $requiredValues = [
                Param::MESSAGE_TYPE_URI => 'LtiResourceLinkRequest',
                Param::VERSION_URI => '1.3.0'
            ];
            if ($state->claims->has(Param::LTI_DEPLOYMENT_ID)) {
                $requireValues[Param::DEPLOYMENT_ID_URI] =
                    $state->claims->get(Param::LTI_DEPLOYMENT_ID);
            }
            $checker = new ParamChecker($jwt->claims->all(), $this->ltiLog);
            $checker->requireValues($requiredValues);
            $checker->requireParams([
                Param::TARGET_LINK_URI_URI,
                Param::RESOURCE_LINK_URI,
                Param::ROLES_URI,
                Param::NONCE,
            ]);
            // TODO: verify and FOLLOW target_link_uri
            // check nonce
            $nonce = $jwt->claims->get(Param::NONCE);
            if (Nonce::isValid($nonce)) Nonce::used($nonce);
            else throw new LtiException($this->ltiLog->msg('Invalid nonce',
                                                           $this->request));
        } catch(\Exception $e) { // invalid signature throws a bare Exception
            throw new LtiException(
                $this->ltiLog->msg('Invalid id_token',$this->request,$e),0,$e);
        }

        return $jwt;
    }

    // decrypt the state and return the JWT, throws LtiException if state invalid
    private function processState(string $token): JWT
    {
        try {
            $jwt = EncryptedState::decrypt($token);
            $this->ltiLog->setStreamId($jwt->claims->get(self::STREAM_ID));
            return $jwt;
        } catch(\Exception $e) {
            throw new LtiException($this->ltiLog->msg(
                'Invalid state in auth response', $this->request, $e), 0, $e);
        }
    }

    // create an encrypted JWT storing params that we expect to see in id_token
    private function createState(int $platformClientId): string
    {
        $claims = [
            self::PLATFORM_CLIENT_ID_PARAM => $platformClientId,
            Param::LOGIN_HINT => $this->request->input(Param::LOGIN_HINT),
            self::STREAM_ID => $this->ltiLog->getStreamId()
        ];
        if ($this->request->has(Param::LTI_DEPLOYMENT_ID)) {
            $claims[Param::LTI_DEPLOYMENT_ID] =
                $this->request->input(Param::LTI_DEPLOYMENT_ID);
        }
        return EncryptedState::encrypt($claims);
    }
}
