<?php
namespace Tests\Feature\LTI\Launch\Tool;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

use Jose\Component\Core\JWK;
use Jose\Easy\Build;

use Tests\TestCase;

use App\Models\Deployment;
use App\Models\EncryptionKey;
use App\Models\Platform;
use App\Models\Tool;

use UBC\LTI\Specs\Security\Nonce;

// only checks incoming requests, for tool, this is the login and the auth resp
class AuthRespTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    private string $baseUrl = '/lti/launch/tool/auth';
    private string $idToken = '';
    private string $state = '';
    private string $loginHint = 'someLoginHint';

    private Deployment $deployment;
    private Platform $targetPlatform;
    private Tool $tool;

    protected function setUp(): void
    {
        parent::setUp();
        // known good request
        $this->tool = factory(Tool::class)->create(['id' => 2]);
        $this->targetPlatform = factory(Platform::class)->create();
        $encryptionKey = factory(EncryptionKey::class)->create();
        $this->deployment = factory(Deployment::class)->create([
            'platform_id' => $this->targetPlatform->id
        ]);
        $this->createIdToken(Nonce::create());
        $time = time();
        $this->state = Build::jwe()
            ->alg('RSA-OAEP-256') // key encryption algo
            ->enc('A256GCM') // content encryption algo
            ->nbf($time)
            ->iat($time)
            ->exp($time + 3600)
            ->claim('original_iss', $this->targetPlatform->iss)
            ->claim('client_id', $this->targetPlatform->shim_client_id)
            ->claim('login_hint', $this->loginHint)
            ->encrypt($encryptionKey->public_key);
    }

    /**
     * If the required params are missing from the authentication resp stage,
     * throw a 400 error.
     *
     * @return void
     */
    public function testProcessAuth()
    {
        $resp = $this->post($this->baseUrl,
            ['state' => $this->state, 'id_token' => $this->idToken]);
        // success should give us a 302 redirect
        $resp->assertStatus(Response::HTTP_FOUND);

        // can't use get requests for the authentication resp
        $resp = $this->get($this->baseUrl);
        $resp->assertStatus(Response::HTTP_METHOD_NOT_ALLOWED);
        // requests that are missing a required param
        $resp = $this->post($this->baseUrl);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
        $resp = $this->post($this->baseUrl, ['state' => $this->state]);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
        $resp = $this->post($this->baseUrl, ['id_token' => $this->idToken]);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);

    }

    public function testRejectUsedNonce()
    {
        $nonce = Nonce::create();
        Nonce::used($nonce); // mark this nonce as used
        $this->createIdToken($nonce);

        $resp = $this->post($this->baseUrl,
            ['state' => $this->state, 'id_token' => $this->idToken]);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testRejectExpiredNonce()
    {
        $nonce = Nonce::create();
        $this->createIdToken($nonce);
        // set the nonce expiration to the past
        DB::table('cache_nonce')->update(['expiration' => time() - 5]);

        $resp = $this->post($this->baseUrl,
            ['state' => $this->state, 'id_token' => $this->idToken]);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testRejectExpiredIdToken()
    {
        $nonce = Nonce::create();
        $this->createIdToken($nonce, true);

        $resp = $this->post($this->baseUrl,
            ['state' => $this->state, 'id_token' => $this->idToken]);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    private function createIdToken(string $nonce, bool $isExpired=false)
    {
        $time = time();
        if ($isExpired) {
            $time -= 3601;
        }
        $this->idToken = Build::jws()
            ->alg('RS256')
            ->iat($time)
            ->exp($time + 3600)
            ->iss($this->targetPlatform->iss)
            ->aud($this->targetPlatform->shim_client_id)
            ->sub($this->loginHint)
            ->claim('nonce', $nonce)
            ->claim('https://purl.imsglobal.org/spec/lti/claim/message_type',
                    'LtiResourceLinkRequest')
            ->claim('https://purl.imsglobal.org/spec/lti/claim/version',
                    '1.3.0')
            ->claim('https://purl.imsglobal.org/spec/lti/claim/deployment_id',
                    $this->deployment->lti_deployment_id)
            ->claim('https://purl.imsglobal.org/spec/lti/claim/target_link_uri',
                    'http://example.com')
            ->claim('https://purl.imsglobal.org/spec/lti/claim/resource_link',
                    ['id' => 'fakeResourceId'])
            ->claim('https://purl.imsglobal.org/spec/lti/claim/roles',
               ['http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor'])
            ->claim('https://purl.imsglobal.org/spec/lti/claim/custom',
                    ['target_tool_id' => $this->tool->id])
            ->sign($this->targetPlatform->getKey()->key);
    }
}
