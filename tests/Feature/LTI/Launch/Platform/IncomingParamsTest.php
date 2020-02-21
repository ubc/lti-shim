<?php

namespace Tests\Feature\LTI\Launch\Platform;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Symfony\Component\HttpFoundation\Response;

use Jose\Easy\Build;

use App\Models\Deployment;
use App\Models\EncryptionKey;
use App\Models\LtiSession;
use App\Models\LtiUser;
use App\Models\Platform;
use App\Models\Tool;

use Tests\TestCase;

// only tests the incoming requests for the platform, this is just the auth req
class IncomingParamsTest extends TestCase
{
    /**
     * If required LTI params are missing from the login, throw a 400 error.
     *
     * @return void
     */
    public function testAuthReqRequiredParams()
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
                'https://purl.imsglobal.org/spec/lti/claim/roles' => []
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

        // check the static values first
        $goodValues = [
            'scope' => 'openid',
            'response_type' => 'id_token',
            'response_mode' => 'form_post',
            'login_hint' => $ltiUser->fake_login_hint,
            'client_id' => $tool->client_id,
            'prompt' => 'none',
            'lti_message_hint' => $encryptedSession
        ];
        $response = $this->call('get', $baseUrl, $goodValues);
        $response->assertStatus(Response::HTTP_OK);
        // no params
        $response = $this->get($baseUrl);
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        // missing values
        foreach ($goodValues as $key => $val) {
            // param has a bad value
            $badValues = $goodValues;
            $badValues[$key] = $val . 'bad';
            $response = $this->call('get', $baseUrl, $badValues);
            $response->assertStatus(Response::HTTP_BAD_REQUEST);
            // param is completely missing
            $badValues = $goodValues;
            unset($badValues[$key]);
            $response = $this->call('get', $baseUrl, $badValues);
            $response->assertStatus(Response::HTTP_BAD_REQUEST);
        }
    }
}
