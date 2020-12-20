<?php

namespace Tests\Feature\LTI\Launch\Platform;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Symfony\Component\HttpFoundation\Response;

use Jose\Easy\Build;
use Jose\Easy\Load;

use App\Models\Ags;
use App\Models\AgsLineitem;
use App\Models\CourseContext;
use App\Models\Deployment;
use App\Models\EncryptionKey;
use App\Models\LtiSession;
use App\Models\LtiFakeUser;
use App\Models\LtiRealUser;
use App\Models\Nrps;
use App\Models\Platform;
use App\Models\Tool;

use Tests\TestCase;

// only tests the incoming requests for the platform, this is just the auth req
class GradebookMessageTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    private string $baseUrl = '/lti/launch/platform/auth';
    private string $ltiMessageHint;
    private string $nonce = "someNonceHere";

    private array $goodValues;

    private CourseContext $courseContext;
    private Deployment $deployment;
    private EncryptionKey $encryptionKey;
    private LtiFakeUser $fakeUser;
    private LtiRealUser $realUser;
    private LtiSession $ltiSession;
    private Platform $shimPlatform;
    private Platform $platform;
    private Tool $tool;

    protected function setUp(): void
    {
        parent::setUp();
        // set up a known good request
        $this->tool = Tool::factory()->create();
        $this->shimPlatform = Platform::factory()->create(['id' => 1]);
        $this->platform = Platform::factory()->create(['id' => 2]);
        $this->encryptionKey = EncryptionKey::factory()->create();
        $this->deployment = Deployment::factory()->create([
            'platform_id' => $this->shimPlatform->id
        ]);
        $this->realUser = LtiRealUser::factory()->create([
            'platform_id' => $this->platform->id
        ]);
        $this->courseContext = CourseContext::factory()->create([
            'deployment_id' => $this->deployment->id,
            'tool_id' => $this->tool->id
        ]);
        $this->fakeUser = LtiFakeUser::factory()->create([
            'lti_real_user_id' => $this->realUser->id,
            'course_context_id' => $this->courseContext->id,
            'tool_id' => $this->tool->id
        ]);
        // prepare session
        $this->ltiSession = LtiSession::factory()->create([
            'token' => [
                'sub' => $this->realUser->sub,
                'https://purl.imsglobal.org/spec/lti/claim/roles' => [],
                'https://purl.imsglobal.org/spec/lti/claim/resource_link' =>
                    ['id' => 'SomeResourceLinkId'],
                'https://purl.imsglobal.org/spec/lti/claim/context' =>
                    ['id' => $this->courseContext->real_context_id],
                'name' => $this->realUser->name,
                'email' => $this->realUser->email,
                'https://purl.imsglobal.org/spec/lti/claim/message_type' =>
                    'LtiSubmissionReviewRequest'
            ],
            'lti_real_user_id' => $this->realUser->id,
            'course_context_id' => $this->courseContext->id,
            'tool_id' => $this->tool->id,
            'deployment_id' => $this->deployment->id,
        ]);
        // create an encrypted jwt to pass the LtiSession, passed as lti
        // message hint
        $time = time();
        $this->ltiMessageHint = Build::jwe()
            ->exp($time + 3600)
            ->iat($time)
            ->nbf($time)
            ->alg('RSA-OAEP-256')
            ->enc('A256GCM')
            ->claim('lti_session', $this->ltiSession->id)
            ->encrypt($this->encryptionKey->public_key);
        $this->goodValues = [
            'scope' => 'openid',
            'response_type' => 'id_token',
            'response_mode' => 'form_post',
            'login_hint' => $this->fakeUser->login_hint,
            'client_id' => $this->tool->client_id,
            'prompt' => 'none',
            'nonce' => $this->nonce,
            'lti_message_hint' => $this->ltiMessageHint
        ];
    }

    // we can add additional claims to $ltiSession to test the platform's
    // claim filtering ability
    private function addClaims(array $claims)
    {
        $this->ltiSession->token = array_merge($this->ltiSession->token,
            $claims);
        $this->ltiSession->save();
    }

    // reconstructing the id_token is a bit difficult, so we'll decode it
    // and check the claims instead of trying to make sure the JWT matches up
    private function getJwtFromResponse($response)
    {
        $token = $response->getOriginalContent()
                          ->getData()['response']['id_token'];
        $this->platformKey = $this->shimPlatform->keys()->first();
        $jwt = Load::jws($token)
            ->algs(['RS256'])
            ->exp()
            ->iat(2000)
            ->nbf()
            ->aud($this->tool->client_id)
            ->iss(config('lti.iss'))
            ->sub($this->fakeUser->sub)
            ->key($this->platformKey->public_key)
            ->run();
        return $jwt;
    }

    /**
     * Make sure that the the message type indicates this is a gradebook
     * message.
     *
     * @return void
     */
    public function testGradebookMessageTypeIsSet()
    {
        // check the static values first
        $response = $this->call('get', $this->baseUrl, $this->goodValues);
        $response->assertStatus(Response::HTTP_OK);
        // make sure where we send the response is right
        $response->assertViewHas('auth_resp_url',
                                 $this->shimPlatform->auth_resp_url);
        $jwt = $this->getJwtFromResponse($response);
        $this->assertEquals(
            'LtiSubmissionReviewRequest',
            $jwt->claims->get(
                'https://purl.imsglobal.org/spec/lti/claim/message_type')
        );
    }

}
