<?php
namespace UBC\LTI\Specs\Launch;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Easy\Build;

use App\Models\Deployment;
use App\Models\Platform;
use App\Models\Tool;

use UBC\LTI\LTIException;
use UBC\LTI\Param;
use UBC\LTI\Specs\ParamChecker;


// we're acting as the Platform
// the main idea is that we supply this object with the params that we receive
// and get the appropriate response params back
class PlatformLaunch
{
    private Request $request; // laravel request object
    private ParamChecker $checker;

    private Deployment $deployment;
    private Tool $tool;

    private bool $hasAuthRequest = false; // true if checkAuthRequest() passed

    private const TOOL_ID = 'toolId';
    private const DEPLOYMENT_ID = 'deploymentId';

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->checker = new ParamChecker($request->input());
        if (session(self::TOOL_ID)) {
            $this->tool = Tool::find(session(self::TOOL_ID));
        }
        if (session(self::DEPLOYMENT_ID)) {
            $this->deployment = Deployment::find(session(self::DEPLOYMENT_ID));
        }
    }

    // first stage of the LTI launch on the platform side, we need to send the
    // login params to the tool.
    public function getLoginParams(): array
    {
        if (!$this->request->session()->has('original_iss')) {
            throw new LTIException('No LTI launch to forward.');
        }
        $platform = Platform::where(Param::ISS, session('original_iss'))->first();
        if (!$platform) throw new LTIException('Could not find platform!');
        // TODO: can probably deserialize in the constructor
        $idToken = (new CompactSerializer())
            ->unserialize(session(Param::ID_TOKEN));
        $idToken = json_decode($idToken->getPayload(), true);
        $deploymentId = $idToken[Param::DEPLOYMENT_ID_URI];
        $deployment = Deployment::where([
            ['deployment_id', $deploymentId],
            ['platform_id', $platform->id]
        ])->first();
        // TODO: create deployment on the fly if it doesn't exist?
        if (!$deployment) throw new LTIException('No deployment found!');
        $tool = $deployment->tool;
        // store database id so we can use it later
        session([
            self::TOOL_ID => $tool->id,
            self::DEPLOYMENT_ID => $deployment->id
        ]);
        $params = [
            Param::ISS => config('lti.iss'),
            Param::LOGIN_HINT => session(Param::LOGIN_HINT), // TODO: filter param
            Param::TARGET_LINK_URI => $tool->target_link_uri,
            Param::CLIENT_ID => $tool->client_id,
            Param::LTI_DEPLOYMENT_ID => $deploymentId
        ];
        return $params;
    }

    // second stage of LTI launch on the platform side, we need to check
    // the authentication request sent by the tool.
    public function checkAuthRequest()
    {
        $tool = Tool::find(session(self::TOOL_ID));
        $requiredValues = [
            // static values
            Param::SCOPE => Param::OPENID,
            Param::RESPONSE_TYPE => Param::ID_TOKEN,
            Param::RESPONSE_MODE => Param::FORM_POST,
            Param::PROMPT => Param::NONE,
            // dynamic values
            Param::LOGIN_HINT => session(Param::LOGIN_HINT),
            Param::CLIENT_ID => $tool->client_id
        ];
        // TODO: validate redirect_uri, valid redirect_uri is supposed to be
        // pre-registered and we need to make sure it matches what we have
        // TODO: nonce validation will probably needs to be tied to client_id
        // and other such dynamic values somehow, so we can be sure that the
        // original login request came from us

        $this->checker->requireValues($requiredValues);

        $this->hasAuthRequest = true;
    }

    // third and final stage of the LTI launch on the platform side, we need
    // to generate the id_token JWT
    public function getAuthResponse(): array
    {
        $tool = Tool::find(session(self::TOOL_ID));
        $deployment = Deployment::find(session(self::DEPLOYMENT_ID));
        // cannot generate the auth response without an auth request
        if (!$this->hasAuthRequest) $this->checkAuthRequest();
        $resp = [
            'state' => $this->request->input('state')
        ];

        $time = time();
        $key = Platform::getOwnPlatform()->keys()->first();
        $token = Build::jws()
            ->typ('JWT')
            ->alg(Param::RS256)
            ->iss(config('lti.iss'))
            ->sub(session(Param::LOGIN_HINT)) // user id
            ->aud($tool->client_id) // same as client_id previously
            ->exp($time + 86400) // expires in 1 hour
            ->iat($time) // issued at
            ->header(Param::KID, $key->kid)
            ->claim(Param::NONCE, $this->request->input('nonce'))
            ->claim(Param::MESSAGE_TYPE_URI, 'LtiResourceLinkRequest')
            ->claim(Param::ROLES_URI, [])
            ->claim(Param::VERSION_URI, '1.3.0')
            ->claim(Param::DEPLOYMENT_ID_URI, $deployment->deployment_id)
            ->claim(Param::TARGET_LINK_URI_URI, $this->tool->target_link_uri)
            // TODO real resource link
            ->claim(Param::RESOURCE_LINK_URI, ['id' => 'fake_resource_link_id'])
            ->sign($key->key);
        $resp['id_token'] = $token;

        return $resp;
    }
}
