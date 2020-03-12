<?php

namespace Tests\Feature\LTI\Launch\Platform;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Symfony\Component\HttpFoundation\Response;

use Jose\Easy\Build;

use App\Models\Deployment;
use App\Models\EncryptionKey;
use App\Models\LtiSession;
use App\Models\LtiRealUser;
use App\Models\LtiFakeUser;
use App\Models\Platform;
use App\Models\Tool;

use Tests\TestCase;

// only tests the incoming requests for the platform, this is just the auth req
class OidcLoginTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    /**
     * If required LTI params are missing from the login, throw a 400 error.
     *
     * @return void
     */
    public function testGetLoginParams()
    {
		$baseUrl = '/lti/launch/platform/login';
        // known good request
        $tool = factory(Tool::class)->create();
        $shimPlatform = factory(Platform::class)->create(['id' => 1]);
        $platform = factory(Platform::class)->create(['id' => 2]);
        $encryptionKey = factory(EncryptionKey::class)->create();
        $deployment = factory(Deployment::class)->create([
            'platform_id' => $shimPlatform->id
        ]);
        $realUser = factory(LtiRealUser::class)->create([
            'platform_id' => $platform->id
        ]);
        $fakeUser = factory(LtiFakeUser::class)->create([
            'lti_real_user_id' => $realUser->id,
            'tool_id' => $tool->id
        ]);
        // prepare session
        $ltiSession = factory(LtiSession::class)->create([
            'token' => [
                'sub' => $realUser->sub,
                'https://purl.imsglobal.org/spec/lti/claim/roles' => []
            ],
            'lti_real_user_id' => $realUser->id,
            'tool_id' => $tool->id,
            'deployment_id' => $deployment->id,
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
            'lti_message_hint' => $encryptedSession
        ];
        $response = $this->call('get', $baseUrl, $goodValues);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewHas('response.iss', config('lti.iss'));
        $response->assertViewHas('response.login_hint',
                                 $fakeUser->login_hint);
        $response->assertViewHas('response.target_link_uri',
                                 $tool->target_link_uri);
        $response->assertViewHas('response.client_id', $tool->client_id);
        $deployment = $deployment->fresh(); // need to reload the model since
                                            // it was modified by the call
        $response->assertViewHas('response.lti_deployment_id',
                                 $deployment->fake_lti_deployment_id);
        $response->assertViewHas('response.lti_message_hint', $encryptedSession);
    }
}
