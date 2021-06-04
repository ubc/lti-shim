<?php

namespace Tests\Feature\LTI\Launch;

use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

use Symfony\Component\HttpFoundation\Response;

use League\Uri\Uri;

use Tests\Feature\LTI\LtiBasicTestCase;

use Database\Seeders\BasicTestDatabaseSeeder;

use App\Models\LtiSession;

// tests the first stage of launch, mainly the LoginHandler
class LoginTest extends LtiBasicTestCase
{
    // hardcoded as a check that the router is using the urls we expect
    private string $loginUrlBase = '/lti/launch/login';
    private string $loginUrl = '';
    private array $basicLoginParams = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->ltiSession->delete(); // delete the seed session
        $this->loginUrl = $this->loginUrlBase . '?target_tool_id=' .
                          $this->tool->id;
        $this->basicLoginParams = [
            'iss' => $this->platform->iss,
            'login_hint' => 'StoreMeInLtiSession',
            'target_link_uri' => $this->tool->shim_target_link_uri
        ];
    }

    /**
     * Test a minimal launch sent using a POST request.
     */
    public function testMinimalLoginUsingPost()
    {
        // no LtiSession right now but should get one by login
        $this->assertEmpty(LtiSession::first());
        // call the shim results endpoint
        $resp = $this->post($this->loginUrl, $this->basicLoginParams);
        $this->checkLoginResponse($resp, $this->basicLoginParams);
    }

    /**
     * The launch's login stage is required to support both POST and GET.
     */
    public function testMinimalLoginUsingGet()
    {
        // no LtiSession right now but should get one by login
        $this->assertEmpty(LtiSession::first());
        // call the shim results endpoint
        $resp = $this->call('GET', $this->loginUrl, $this->basicLoginParams);
        $this->checkLoginResponse($resp, $this->basicLoginParams);
    }

    /**
     * Test that optional params which needs to be used in the next stages
     * of LTI launch are properly stored in LtiSession.
     */
    public function testOptionalParamsArePersistedInLtiSession()
    {
        $params = $this->basicLoginParams;
        $params['lti_message_hint'] = 'StoreThisInLtiSessionToo';
        $params['lti_deployment_id'] = 'AlsoStoreThisInLtiSessionPlease';
        $resp = $this->post($this->loginUrl, $params);
        $this->checkLoginResponse($resp, $params);
    }

    /**
     * Check that the login response is ok and has created an LtiSession entry
     * as expected.
     */
    private function checkLoginResponse(
        TestResponse $resp,
        array $expectedParams
    ) {
        $resp->assertStatus(Response::HTTP_OK);
        // login should have created 1 LtiLogin
        $this->assertEquals(1, LtiSession::count());
        $session = LtiSession::first();
        $this->assertEquals($this->tool->id, $session->tool_id);
        $this->assertEquals($this->platformClient->id,
                            $session->platform_client_id);
        // default val for log_stream is 'Unavailable', we don't want that
        $this->assertNotEquals('Unavailable', $session->log_stream);
        // check that session has required params
        $this->assertEquals($expectedParams['login_hint'],
                            $session->token['login_hint']);
        // check that session has optional params, if present
        if (array_key_exists('lti_message_hint', $expectedParams)) {
            $this->assertEquals($expectedParams['lti_message_hint'],
                                $session->token['lti_message_hint']);
        }
        if (array_key_exists('lti_deployment_id', $expectedParams)) {
            $this->assertEquals($expectedParams['lti_deployment_id'],
                                $session->token['lti_deployment_id']);
        }
        // check that returned view has params to send login to target tool
        $resp->assertViewHas('params.iss', config('lti.iss'));
        $resp->assertViewHas('params.target_link_uri',
                             $this->tool->target_link_uri);
        $resp->assertViewHas('params.client_id',
                             $this->tool->client_id);
        // check that login_hint is valid and can get the LtiSession back
        $decodedSession = LtiSession::decodeEncryptedId(
                                                $resp['params']['login_hint']);
        $this->assertNotEmpty($decodedSession);
        $this->assertEquals($session->id, $decodedSession->id);
    }

    /**
     * Error out if a required parameter is missing.
     */
    public function testLoginMissingRequiredParams()
    {
        $params = $this->basicLoginParams;
        unset($params['iss']);
        $resp = $this->post($this->loginUrl, $params);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);

        $params = $this->basicLoginParams;
        unset($params['login_hint']);
        $resp = $this->post($this->loginUrl, $params);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);

        $params = $this->basicLoginParams;
        unset($params['target_link_uri']);
        $resp = $this->post($this->loginUrl, $params);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Error out if we're sent an iss that's not in the database.
     */
    public function testRejectInvalidIss()
    {
        $params = $this->basicLoginParams;
        $params['iss'] = 'InvalidIss';
        $resp = $this->post($this->loginUrl, $params);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Error out if we're sent a target_link_uri that's not the same site as
     * the shim.
     */
    public function testRejectInvalidTargetLinkUri()
    {
        $params = $this->basicLoginParams;
        // we make an invalid target_link_uri by changing the host
        $uri = Uri::createFromString($params['target_link_uri']);
        $params['target_link_uri'] = $uri->withHost('invalid.example.com');
        $resp = $this->post($this->loginUrl, $params);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Error out if we don't know what target tool to use.
     */
    public function testMissingTargetTool()
    {
        $resp = $this->post($this->loginUrlBase, $this->basicLoginParams);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }
}
