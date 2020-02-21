<?php
namespace UBC\LTI\Specs\Launch;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use Jose\Component\Checker\InvalidClaimException;
use Jose\Easy\Build;
use Jose\Easy\JWT;
use Jose\Easy\Load;

use App\Models\Deployment;
use App\Models\LtiSession;
use App\Models\LtiUser;
use App\Models\Platform;
use App\Models\PlatformClient;
use App\Models\Tool;

use UBC\LTI\EncryptedState;
use UBC\LTI\LTIException;
use UBC\LTI\Param;
use UBC\LTI\Specs\ParamChecker;

// we're acting as the Tool
// the main idea is that we supply this object with the params that we receive
// and get the appropriate response params back
class ToolLaunch
{
    private Request $request; // laravel request object
    private ParamChecker $checker;

    private bool $hasLogin = false; // true if checkLogin() passed

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->checker = new ParamChecker($request->input());
    }

    // first stage of the LTI launch on the tool side, we need to check that
    // the platform has sent us all the information we need
    public function checkLogin()
    {
        $requiredParams = [
            Param::ISS,
            Param::LOGIN_HINT,
            Param::TARGET_LINK_URI
        ];
        $this->checker->requireParams($requiredParams);

        // check that the request is coming from a platform we know
        $iss = $this->request->input(Param::ISS);
        $platform = Platform::firstWhere('iss', $iss);
        if (!$platform) throw new LTIException("Unknown platform iss: $iss");
        // make sure that target_link_uri is pointing to us
        $target = $this->request->input(Param::TARGET_LINK_URI);
        if (strpos($target, config('app.url')) !== 0)
            throw new LTIException("target_link_uri is some other site: $target");

        $this->hasLogin = true;
    }

    // second stage of LTI launch on the tool side, we need to send an auth
    // request back to the platform, this function returns the params that
    // should be sent
    public function getLoginResponse(): array
    {
        $ownTool = Tool::getOwnTool();
        // cannot generate the login response if we don't have a valid login
        if (!$this->hasLogin) $this->checkLogin();
        $resp = [
            Param::SCOPE => Param::OPENID,
            Param::RESPONSE_TYPE => Param::ID_TOKEN,
            Param::LOGIN_HINT => $this->request->input(Param::LOGIN_HINT),
            Param::RESPONSE_TYPE => Param::FORM_POST,
            Param::PROMPT => Param::NONE,
            Param::REDIRECT_URI => $ownTool->auth_resp_url
        ];
        // client_id is either given in the request or stored in the database
        if ($this->request->filled(Param::CLIENT_ID)) {
            // client_id is in request, just forward it
            $resp[Param::CLIENT_ID] = $this->request->input(Param::CLIENT_ID);
            // TODO: if this is an unknown client_id, should we add it to the
            // list of known client_id?
        } else {
            // client_id not in request, so retrieve from database
            $iss = $this->request->input(Param::ISS);
            $platform = Platform::firstWhere('iss', $iss);
            $client = PlatformClient::firstWhere('platform_id', $platform->id);
            if (!$client) throw new LTIException("No client_id found for $iss");
            $resp[Param::CLIENT_ID] = $client->client_id;
        }
        // lti_message_hint needs to be passed as is back to the platform
        if ($this->request->filled(Param::LTI_MESSAGE_HINT)) {
            $resp[Param::LTI_MESSAGE_HINT] =
                $this->request->input(Param::LTI_MESSAGE_HINT);
        }

        // storing values into state reduces the amount of bookkeeping we need
        // to do on our side, so I'm putting values that requires verification
        // against the id_token into the state.
        $resp[Param::STATE] = $this->createState();

        // TODO: real nonce security
        $resp[Param::NONCE] = 'fakenonce';

        return $resp;
    }

    // third stage of the LTI launch on the tool side, we need to check the
    // authentication response sent back by the platform
    public function processAuth()
    {
        $requiredParams = [
            Param::STATE,
            Param::ID_TOKEN
        ];
        $this->checker->requireParams($requiredParams);
        $state = $this->processState($this->request->input(Param::STATE));
        $platform = Platform::firstWhere('iss',
                                         $state->claims->get('original_iss'));
        $idToken = $this->processIdToken($this->request->input(Param::ID_TOKEN),
                                       $state,
                                       $platform);
        $deployment = Deployment::firstWhere([
            'lti_deployment_id' => $idToken->claims
                                           ->get(Param::DEPLOYMENT_ID_URI),
            'platform_id' => $platform->id
        ]);
        // persist the session in the database
        $sessionData = $idToken->claims->all();
        $sessionData['deployment_id'] = $deployment->id;
        $sessionData[Param::LOGIN_HINT] = $state->claims->get(Param::LOGIN_HINT);

        $ltiSession = new LtiSession();
        $ltiSession->session = $sessionData;
        $ltiSession->save();
        // need to generate the lti user for use by UserFilter later
        $this->createLtiUser($ltiSession);
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
        Platform $platform
    ): JWT {
        $jwk = $platform->keys()->first()->public_key;
        $jwt;
        try {
            $jwt = Load::jws($token);
        } catch(InvalidArgumentException $e) {
            throw new LTIException('id_token not base64 encoded.', 0, $e);
        }
        $jwt = $jwt->algs([Param::RS256]) // The algorithms allowed to be used
                   ->exp() // We check the "exp" claim
                   ->iat(5000) // We check the "iat" claim. Leeway is 5000ms
                   ->aud($state->claims->get(Param::CLIENT_ID))
                   ->iss($state->claims->get('original_iss'))
                   ->key($jwk); // Key used to verify the signature
        try {
            // check signature
            $jwt = $jwt->run();
            // TODO: check presence of required params
            // check required claim values
            $requiredValues = [
                Param::MESSAGE_TYPE_URI => 'LtiResourceLinkRequest',
                Param::VERSION_URI => '1.3.0'
            ];
            if ($state->claims->has(Param::LTI_DEPLOYMENT_ID)) {
                $requireValues[Param::DEPLOYMENT_ID_URI] =
                    $state->claims->get(Param::LTI_DEPLOYMENT_ID);
            }
            $checker = new ParamChecker($jwt->claims->all());
            $checker->requireValues($requiredValues);
        } catch(\Exception $e) { // invalid signature throws a bare Exception
            throw new LTIException('Invalid id_token: '.$e->getMessage(),0,$e);
        }

        return $jwt;
    }

    // decrypt the state and return the JWT, throws LTIException if state invalid
    private function processState(string $token): JWT
    {
        try {
            $jwt = EncryptedState::decrypt($token);
            return $jwt;
        } catch(\Exception $e) {
            throw new LTIException('Invalid state in auth response: ' .
                $e->getMessage(), 0, $e);
        }
    }

    // create an encrypted JWT storing params that we expect to see in id_token
    private function createState(): string
    {
        $claims = [
            'original_iss' => $this->request->input(Param::ISS),
            Param::CLIENT_ID => $this->request->input(Param::CLIENT_ID),
            Param::LOGIN_HINT => $this->request->input(Param::LOGIN_HINT)
        ];
        if ($this->request->has(Param::LTI_DEPLOYMENT_ID)) {
            $claims[Param::LTI_DEPLOYMENT_ID] =
                $this->request->input(Param::LTI_DEPLOYMENT_ID);
        }
        return EncryptedState::encrypt($claims);
    }

    private function createLtiUser(LtiSession $session)
    {
        $user = LtiUser::firstWhere([
            'real_login_hint' => $session->session[Param::LOGIN_HINT],
            'deployment_id' => $session->session['deployment_id']
        ]);
        if ($user) return; // user already exists

        $user = new LtiUser();
        $user->real_login_hint = $session->session[Param::LOGIN_HINT];
        $user->sub = $session->session[Param::SUB];
        $user->deployment_id = $session->session['deployment_id'];
        if (isset($session->session[Param::NAME])) {
            $user->real_name = $session->session[Param::NAME];
        }
        if (isset($session->session[Param::EMAIL])) {
            $user->real_email = $session->session[Param::EMAIL];
        }
        $user->fillFakeFields();
        $user->save();
    }

}
