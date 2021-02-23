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
use App\Models\PlatformClient;
use App\Models\Tool;

use UBC\LTI\Specs\Security\Nonce;

// since Gradebook Messages only apply to the id_token, we only need to test
// that part. And the only thing we need to do on the tool side is to allow
// the gradebook message type.
class GradebookMessageTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    private string $baseUrl = '/lti/launch/tool/auth';
    private string $idToken = '';
    private string $state = '';
    private string $loginHint = 'someLoginHint';

    private Deployment $deployment;
    private Platform $platform;
    private PlatformClient $client;
    private Tool $tool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        // known good request
        $this->tool = Tool::where('name', 'Ltijs Demo Server')->first();
        $this->platform = Platform::where('iss',
            'https://lti-ri.imsglobal.org')->first(); // RI has seeded key
        $this->client = $this->tool->getPlatformClient($this->platform->id);
        $encryptionKey = EncryptionKey::factory()->create();
        $this->deployment = Deployment::factory()->create([
            'platform_id' => $this->platform->id
        ]);
        $this->createIdToken();
        $time = time();
        $this->state = Build::jwe()
            ->alg('RSA-OAEP-256') // key encryption algo
            ->enc('A256GCM') // content encryption algo
            ->nbf($time)
            ->iat($time)
            ->exp($time + 3600)
            ->claim('platform_client_id', $this->client->id)
            ->claim('login_hint', $this->loginHint)
            ->claim('stream', bin2hex(random_bytes(2)))
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
    }

    public function testRejectUnknownMessageType()
    {
        $this->createIdToken([
            'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'BadMsg'
        ]);

        $resp = $this->post($this->baseUrl,
            ['state' => $this->state, 'id_token' => $this->idToken]);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    private function createIdToken(array $claims=[])
    {
        $time = time();
        $idToken = Build::jws()
            ->alg('RS256')
            ->iat($time)
            ->exp($time + 3600)
            ->iss($this->platform->iss)
            ->aud($this->client->client_id)
            ->sub($this->loginHint)
            ->claim('nonce', Nonce::create())
            ->claim('https://purl.imsglobal.org/spec/lti/claim/message_type',
                    'LtiSubmissionReviewRequest')
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
                ['target_tool_id' => $this->tool->id]);
        // add custom claims
        foreach ($claims as $key => $val) {
            $idToken = $idToken->claim($key, $val);
        }
        $this->idToken = $idToken->sign($this->platform->getKey()->key);
    }
}
