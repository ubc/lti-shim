<?php
namespace UBC\LTI\Specs\Launch;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use UBC\LTI\LTIException;
use UBC\LTI\Specs\RequestChecker;


// the main idea is that we supply this object with the params that we receive
// and get the appropriate response params back
class PlatformLaunch
{
    private Request $request; // laravel request object
    private RequestChecker $checker;

    private bool $hasAuthRequest = false; // true if checkAuthRequest() passed

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->checker = new RequestChecker($request);
    }

    // first stage of the LTI launch on the platform side, we need to send the
    // login params to the tool.
    public function getLoginParams(): array
    {
        $params = [
            'iss' => 'http://localhost/',
            'login_hint' => 'testuser',
            'target_link_uri' => 'https://lti-ri.imsglobal.org/lti/tools/654/launches',
            'client_id' => 'prototype1',
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
            // dynamic values
            'login_hint' => 'testuser',
            'client_id' => 'prototype1',
        ];
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
        if (!$this->hasAuthRequest) $this->checkAuthRequest();
        $resp = [
            'state' => $this->request->input('state')
        ];

    }
}
