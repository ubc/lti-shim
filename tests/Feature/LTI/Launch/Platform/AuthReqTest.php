<?php

namespace Tests\Feature\LTI\Launch\Platform;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Symfony\Component\HttpFoundation\Response;

use Jose\Easy\Build;

use App\Models\CourseContext;
use App\Models\Deployment;
use App\Models\EncryptionKey;
use App\Models\LtiSession;
use App\Models\LtiFakeUser;
use App\Models\LtiRealUser;
use App\Models\Platform;
use App\Models\PlatformClient;
use App\Models\Tool;

use Tests\TestCase;

// only tests the incoming requests for the platform, this is just the auth req
class AuthReqTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    /**
     * If required LTI params are missing from the login, throw a 400 error.
     *
     * @return void
     */
    public function testCheckAuthRequest()
    {
        $baseUrl = '/lti/launch/platform/auth';
        // known good request
        $tool = Tool::factory()->create();
        $shimPlatform = Platform::factory()->create([
            'id' => 1,
            'iss' => config('lti.iss')
        ]);
        $platform = Platform::factory()->create(['id' => 2]);
        $platformClient = PlatformClient::factory()->create([
            'platform_id' => $platform->id,
            'tool_id' => $tool->id
        ]);
        $encryptionKey = EncryptionKey::factory()->create();
        $deployment = Deployment::factory()->create([
            'platform_id' => $shimPlatform->id
        ]);
        $courseContext = CourseContext::factory()->create([
            'tool_id' => $tool->id,
            'deployment_id' => $deployment->id
        ]);
        $realUser = LtiRealUser::factory()->create([
            'platform_id' => $platform->id
        ]);
        $fakeUser = LtiFakeUser::factory()->create([
            'lti_real_user_id' => $realUser->id,
            'course_context_id' => $courseContext->id,
            'tool_id' => $tool->id
        ]);
        // prepare session
        $ltiSession = LtiSession::factory()->create([
            'token' => [
                'sub' => $realUser->sub,
                'https://purl.imsglobal.org/spec/lti/claim/roles' => [],
                'https://purl.imsglobal.org/spec/lti/claim/resource_link' =>
                    ['id' => 'resourceLinkId'],
                'https://purl.imsglobal.org/spec/lti/claim/message_type' =>
                    'LtiResourceLinkRequest'
            ],
            'lti_real_user_id' => $realUser->id,
            'course_context_id' => $courseContext->id,
            'tool_id' => $tool->id,
            'deployment_id' => $deployment->id,
            'platform_client_id' => $platformClient->id
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
            'login_hint' => $fakeUser->login_hint,
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
