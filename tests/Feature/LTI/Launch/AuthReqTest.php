<?php

namespace Tests\Feature\LTI\Launch;

use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

use Symfony\Component\HttpFoundation\Response;

use Tests\Feature\LTI\LtiBasicTestCase;

use Database\Seeders\BasicTestDatabaseSeeder;

use App\Models\LtiSession;

use UBC\LTI\Specs\Security\Nonce;

// tests second stage of launch, mainly the AuthReqHandler
class AuthReqTest extends LtiBasicTestCase
{
    // hardcoded as a check that the router is using the urls we expect
    private string $authUrl = '/lti/launch/auth';
    private array $basicAuthParams = [];

    protected function setUp(): void
    {
        parent::setUp();

        // the LtiSession at this stage wouldn't have all fields populated
        $this->ltiSession->deployment_id = null;
        $this->ltiSession->course_context_id = null;
        $this->ltiSession->lti_real_user_id = null;
        // the OIDC login should've at least left us with a login_hint
        $this->ltiSession->state = [
            'login_hint' => 'OriginalLoginHintFromThePlatform'
        ];
        $this->ltiSession->save();

        $this->basicAuthParams = [
            'scope' => 'openid',
            'response_type' => 'id_token',
            'response_mode' => 'form_post',
            'prompt' => 'none',
            'login_hint' => $this->ltiSession->createEncryptedId(),
            'client_id' => $this->tool->client_id,
            'redirect_uri' => $this->tool->auth_resp_url,
            'nonce' => 'SomeNonceHere'
        ];
    }

    /**
     * Test a minimal launch sent using a POST request.
     */
    public function testMinimalAuthReqUsingPost()
    {
        // call the shim results endpoint
        $resp = $this->post($this->authUrl, $this->basicAuthParams);
        $this->checkSuccessfulResponse($resp, $this->basicAuthParams);
    }

    /**
     * Auth req should work for GET requests too.
     */
    public function testMinimalAuthReqUsingGet()
    {
        $resp = $this->call('GET', $this->authUrl, $this->basicAuthParams);
        $this->checkSuccessfulResponse($resp, $this->basicAuthParams);
    }

    /**
     * If the initial OIDC login from the originating platform included the
     * optional param lti_message_hint, then we need to pass it back to the
     * platform when we send the auth req back.
     */
    public function testLtiMessageHintIsPassed()
    {
        // if the lti_message_hint was present, then it was stored in
        // LtiSession
        $this->ltiSession->state += ['lti_message_hint' => 'DOIE*#)$_@KDUFJMN'];
        $this->ltiSession->save();

        $resp = $this->post($this->authUrl, $this->basicAuthParams);
        $this->checkSuccessfulResponse($resp, $this->basicAuthParams);
    }

    /**
     * When the auth req from the target tool contains the optional param
     * state, we need to save them to the LtiSession since it is used in the
     * next auth resp step.
     */
    public function testStateIsSavedToLtiSession()
    {
        $params = $this->basicAuthParams;
        $params['state'] = 'ThisIsSomeStateThatNeedsToBeStoredInLtiSessions';

        $resp = $this->post($this->authUrl, $params);
        $this->checkSuccessfulResponse($resp, $params);
    }

    /**
     * Check that the returned auth req has all the expected values
     */
    private function checkSuccessfulResponse(
        TestResponse $resp,
        array $sentParams
    ) {
        $resp->assertStatus(Response::HTTP_OK);
        // static values
        $resp->assertViewHas('params.scope', 'openid');
        $resp->assertViewHas('params.prompt', 'none');
        $resp->assertViewHas('params.response_type', 'id_token');
        $resp->assertViewHas('params.response_mode', 'form_post');
        // dynamic values
        $resp->assertViewHas('params.login_hint',
                             $this->ltiSession->state['login_hint']);
        $resp->assertViewHas('params.client_id',
                             $this->platformClient->client_id);
        $resp->assertViewhas('params.redirect_uri',
                             'http://localhost/lti/launch/redirect');
        // make sure state can be decoded
        $decodedSession = LtiSession::decodeEncryptedId(
                                                      $resp['params']['state']);
        $this->assertNotEmpty($decodedSession);
        $this->assertEquals($this->ltiSession->id, $decodedSession->id);
        // make sure nonce is valid and is stored in LtiSession
        $this->ltiSession->refresh(); // regrab the model from the database
        $this->assertTrue(isset($resp['params']['nonce']));
        $this->assertTrue(Nonce::isValid($resp['params']['nonce']));
        $this->assertEquals($sentParams['nonce'],
                            $this->ltiSession->state['nonce']);
        // check if the optional lti_message_hint is there
        if (isset($this->ltiSession->state['lti_message_hint'])) {
            $resp->assertViewHas('params.lti_message_hint',
                                 $this->ltiSession->state['lti_message_hint']);
        }
        else {
            $resp->assertViewMissing('params.lti_message_hint');
        }
        // check redirect_uri is persisted
        $this->assertEquals($sentParams['redirect_uri'],
                            $this->ltiSession->state['redirect_uri']);
        // check if the optional state is persisted
        if (isset($sentParams['state'])) {
            $this->assertEquals($sentParams['state'],
                                $this->ltiSession->state['state']);
        }
    }

    public function testAuthReqMissingRequiredParams()
    {
        $paramKeys = array_keys($this->basicAuthParams);
        foreach ($paramKeys as $paramKey) {
            $params = $this->basicAuthParams;
            unset($params[$paramKey]);

            $resp = $this->post($this->authUrl, $params);
            $resp->assertStatus(Response::HTTP_BAD_REQUEST);
        }
    }

    public function testAuthReqRequiredParamsWrongValue()
    {
        $paramKeys = array_keys($this->basicAuthParams);
        foreach ($paramKeys as $paramKey) {
            // this is a nonce generated by the target tool and so will be
            // checked by the target tool in the auth resp step, we can skip it
            // since it's not our responsibility.
            if ($paramKey == 'nonce') continue;

            $params = $this->basicAuthParams;
            $params[$paramKey] = 'BADVALUE';

            $resp = $this->post($this->authUrl, $params);
            $resp->assertStatus(Response::HTTP_BAD_REQUEST);
        }
    }
}
