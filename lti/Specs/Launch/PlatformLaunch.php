<?php
namespace UBC\LTI\Specs\Launch;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use Jose\Easy\Build;

use UBC\LTI\LTIException;
use UBC\LTI\Specs\ParamChecker;
use UBC\LTI\KeyStorage;


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
        $params = [
            'iss' => 'http://localhost',
            'login_hint' => 'testuser',
            'target_link_uri' => 'http://localhost:9001/game.php',
            'client_id' => 'StrawberryCat',
            'lti_deployment_id' => 'prototype1'
        ];
        return $params;
    }

    // second stage of LTI launch on the platform side, we need to check
    // the authentication request sent by the tool.
    public function checkAuthRequest()
    {
        $requiredValues = [
            // hardcoded values
            'scope' => 'openid',
            'response_type' => 'id_token',
            'response_mode' => 'form_post',
            'prompt' => 'none',
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
