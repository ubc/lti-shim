<?php
namespace UBC\LTI\Specs\Launch;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Easy\Build;

use App\Models\Deployment;
use App\Models\Platform;

use UBC\LTI\LTIException;
use UBC\LTI\KeyStorage;
use UBC\LTI\Param;
use UBC\LTI\Specs\ParamChecker;


// the main idea is that we supply this object with the params that we receive
// and get the appropriate response params back
class PlatformLaunch
{
    private Request $request; // laravel request object
    private ParamChecker $checker;

    private bool $hasAuthRequest = false; // true if checkAuthRequest() passed

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->checker = new ParamChecker($request->input());
    }

    // first stage of the LTI launch on the platform side, we need to send the
    // login params to the tool.
    public function getLoginParams(): array
    {
        if (!$this->request->session()->has('original_iss')) {
            throw new LTIException('No LTI launch to forward.');
        }
        $platform = Platform::where(Param::ISS, session('original_iss'))->first();
        // TODO: can probably deserialize in the constructor
        $idToken = (new CompactSerializer())
            ->unserialize(session(Param::ID_TOKEN));
        $idToken = json_decode($idToken->getPayload(), true);
        $deploymentId = $idToken[Param::DEPLOYMENT_ID_URI];
        $deployment = Deployment::where([
            ['deployment_id', $deploymentId],
            ['platform_id', $platform->id]
        ])->first();
        $tool = $deployment->tool;
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
        $requiredValues = [
            // static values
            Param::SCOPE => Param::OPENID,
            Param::RESPONSE_TYPE => Param::ID_TOKEN,
            Param::RESPONSE_MODE => Param::FORM_POST,
            Param::PROMPT => Param::NONE,
            // dynamic values
            Param::LOGIN_HINT => session(Param::LOGIN_HINT)
        ];
        // TODO: validate login_hint and client_id
        // TODO: lti_message_hint needs to be a dynamic value
        // TODO: validate redirect_uri
        // TODO: nonce validation will probably needs to be tied to client_id
        // and other such dynamic values somehow, so we can be sure that the
        // original login request came from us

        $this->checker->requireValues($requiredValues);

        $this->hasAuthRequest = true;
    }

    // third and final stage of the LTI launch on the platform side, we need
    // to generate the id_token JWT
    public function getAuthResponse()
    {
        // cannot generate the auth response without an auth request
        if (!$this->hasAuthRequest) $this->checkAuthRequest();
        $resp = [
            'state' => $this->request->input('state')
        ];

        $time = time();
        $key = KeyStorage::getMyPrivateKey();
        $token = Build::jws()
            ->typ('JWT')
            ->alg('RS256')
            ->iss('http://localhost')
            ->sub('testuser') // user id, same as login_hint
            ->aud('StrawberryCat') // same as client_id previously
            ->claim('azp', 'StrawberryCat')
            ->exp($time + 86400) // expires in 1 hour
            ->iat($time) // issued at
            ->header('kid', 'MyDummyKey')
            ->claim('nonce', $this->request->input('nonce'))
            ->claim('https://purl.imsglobal.org/spec/lti/claim/message_type',
                'LtiResourceLinkRequest')
            ->claim('https://purl.imsglobal.org/spec/lti/claim/roles', [])
            ->claim('https://purl.imsglobal.org/spec/lti/claim/version',
                '1.3.0')
            ->claim('https://purl.imsglobal.org/spec/lti/claim/deployment_id',
                'prototype1')
            ->claim('https://purl.imsglobal.org/spec/lti/claim/target_link_uri',
                'https://lti-ri.imsglobal.org/lti/tools/654/launches')
            ->claim('https://purl.imsglobal.org/spec/lti/claim/resource_link',
               ['id' => 'fake_resource_link_id'])
            ->sign($key);
        $resp['id_token'] = $token;

        return $resp;
    }
}
