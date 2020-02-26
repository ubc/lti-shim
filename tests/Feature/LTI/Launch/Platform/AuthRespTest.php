<?php

namespace Tests\Feature\LTI\Launch\Platform;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Symfony\Component\HttpFoundation\Response;

use Jose\Easy\Build;
use Jose\Easy\Load;

use App\Models\Deployment;
use App\Models\EncryptionKey;
use App\Models\LtiSession;
use App\Models\LtiUser;
use App\Models\Platform;
use App\Models\Tool;

use Tests\TestCase;

// only tests the incoming requests for the platform, this is just the auth req
class AuthRespTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    /**
     * If required LTI params are missing from the login, throw a 400 error.
     *
     * @return void
     */
    public function testGetAuthResponse()
    {
        $baseUrl = '/lti/launch/platform/auth';
        // known good request
        $tool = factory(Tool::class)->create();
        $myPlatform = factory(Platform::class)->create(['id' => 1]);
        $encryptionKey = factory(EncryptionKey::class)->create();
        $deployment = factory(Deployment::class)->create([
            'tool_id' => $tool->id,
            'platform_id' => $myPlatform->id
        ]);
        $ltiUser = factory(LtiUser::class)->create([
            'deployment_id' => $deployment->id
        ]);
        // prepare session
        $ltiSession = factory(LtiSession::class)->create([
            'session' => [
                'login_hint' => $ltiUser->real_login_hint,
                'tool_id' => $tool->id,
                'deployment_id' => $deployment->id,
                'sub' => $ltiUser->sub,
                'https://purl.imsglobal.org/spec/lti/claim/roles' => [],
                'name' => $ltiUser->real_name,
                'email' => $ltiUser->real_email
            ]
        ]);
        $time = time();
        $encryptedSession = Build::jwe()
            ->exp($time + 3600)
            ->iat($time)
            ->nbf($time)
            ->alg('RSA-OAEP-256')
            ->enc('A256GCM')
            ->claim('lti_session', $ltiSession->id)
            ->encrypt($encryptionKey->public_key);
        $nonce = 'someNonce';

        // check the static values first
        $goodValues = [
            'scope' => 'openid',
            'response_type' => 'id_token',
            'response_mode' => 'form_post',
            'login_hint' => $ltiUser->fake_login_hint,
            'client_id' => $tool->client_id,
            'prompt' => 'none',
            'nonce' => $nonce,
            'lti_message_hint' => $encryptedSession
        ];
        $response = $this->call('get', $baseUrl, $goodValues);
        $response->assertStatus(Response::HTTP_OK);
        // make sure where we send the response is right
        $response->assertViewHas('auth_resp_url', $myPlatform->auth_resp_url);
        // reconstructing the id_token is a bit difficult, so we'll decode it
        // and verify it that way instead
        $token = $response->getOriginalContent()
                          ->getData()['response']['id_token'];
        $platformKey = $myPlatform->keys()->first();
        $jwt = Load::jws($token)
            ->algs(['RS256'])
            ->exp()
            ->iat(2000)
            ->nbf()
            ->aud($tool->client_id)
            ->iss(config('lti.iss'))
            ->sub($ltiUser->fake_login_hint)
            ->key($platformKey->public_key)
            ->run();
        $response->assertViewMissing('response.state');
        // test filters
        $this->assertEquals($ltiUser->fake_name, $jwt->claims->get('name'));
        $this->assertEquals($ltiUser->fake_email, $jwt->claims->get('email'));
        // test required params
        $this->assertEquals($nonce, $jwt->claims->get('nonce'));
        $this->assertEquals('JWT', $jwt->claims->get('typ'));
        $this->assertEquals($platformKey->kid, $jwt->claims->get('kid'));
        $this->assertEquals(
            'LtiResourceLinkRequest',
            $jwt->claims->get(
                'https://purl.imsglobal.org/spec/lti/claim/message_type')
        );
        $this->assertEquals(
            '1.3.0',
            $jwt->claims->get(
                'https://purl.imsglobal.org/spec/lti/claim/version')
        );
        $this->assertEquals(
            $deployment->lti_deployment_id,
            $jwt->claims->get(
                'https://purl.imsglobal.org/spec/lti/claim/deployment_id')
        );
        $this->assertEquals(
            $tool->target_link_uri,
            $jwt->claims->get(
                'https://purl.imsglobal.org/spec/lti/claim/target_link_uri')
        );
        // TODO: test resource link once implemented
        $this->assertNotNull(
            $jwt->claims->get(
                'https://purl.imsglobal.org/spec/lti/claim/resource_link')
        );
        $this->assertNotNull(
            $jwt->claims->get(
                'https://purl.imsglobal.org/spec/lti/claim/roles')
        );
        // check state is passed properly if included
        $state = 'someFakeState';
        $goodValues['state'] = $state;
        $response = $this->call('get', $baseUrl, $goodValues);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewHas('response.state', $state);
    }
}
