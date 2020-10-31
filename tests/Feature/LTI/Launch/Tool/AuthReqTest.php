<?php
namespace Tests\Feature\LTI\Launch\Tool;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

use Tests\TestCase;

use App\Models\EncryptionKey;
use App\Models\Platform;
use App\Models\PlatformClient;
use App\Models\Tool;

// only checks incoming requests, for tool, this is the login and the auth resp
class AuthReqTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    private const LOGIN_HINT = 'expectedLoginHint';

    private string $baseUrl = '/lti/launch/tool/login';
    private Tool $tool;
    private Tool $shimTool;
    private Platform $platform;
    private PlatformClient $platformClient;
    private array $goodParams;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->tool = Tool::find(2);
        $this->shimTool = Tool::getOwnTool();
        $this->platform = Platform::find(2);
        $this->platformClient = $this->tool->getPlatformClient(
                                                        $this->platform->id);
        $this->goodParams = [
            'iss' => $this->platform->iss,
            'login_hint' => self::LOGIN_HINT,
            'target_link_uri' => $this->tool->shim_target_link_uri
        ];

        $encryptionKey = EncryptionKey::factory()->create();
    }

    // make sure that the data we want to pass back to the platform is correct
    public function testMinimalOidcParams()
    {
        $resp = $this->call('get', $this->baseUrl, $this->goodParams);
        $resp->assertStatus(Response::HTTP_OK);
        $resp->assertViewHas('auth_req_url', $this->platform->auth_req_url);
        // can't figure out how to calculate the exact same 'state',
        // so we can't use the assertViewHasAll() function
        $resp->assertViewHas('response.scope', 'openid');
        $resp->assertViewHas('response.response_type', 'id_token');
        $resp->assertViewHas('response.client_id',
                             $this->platformClient->client_id);
        $resp->assertViewHas('response.redirect_uri',
                             $this->shimTool->auth_resp_url);
        $resp->assertViewHas('response.login_hint', self::LOGIN_HINT);
        $resp->assertViewHas('response.response_mode', 'form_post');
        $resp->assertViewHas('response.prompt', 'none');
        // test nonce is properly stored in the database
        $nonceResult = DB::table('cache_nonce')->first();
        $nonce = str_replace('lti_shim_cache', '', $nonceResult->key);
        $resp->assertViewHas('response.nonce', $nonce);
    }

    public function testLtiMessageHintPassthrough()
    {
        $expectedMessageHint = 'expectedMessageHint';

        $goodParams = $this->goodParams;
        $goodParams['lti_message_hint'] = $expectedMessageHint;

        $resp = $this->call('get', $this->baseUrl, $goodParams);
        $resp->assertStatus(Response::HTTP_OK);
        $resp->assertViewHas('response.lti_message_hint', $expectedMessageHint);
    }

    // client_id is an optional OIDC param that, if provided, we can use to
    // retrieve the associated PlatformClient to verify that the tool is
    // properly registered. This test includes a client_id so we can test that
    // it properly deals with the client_id.
    public function testGetLoginResponseWithClientId()
    {
        $goodParams = $this->goodParams;
        // purposely cripple the target_link_uri so that it cannot be used to
        // identify the target tool.
        $goodParams['target_link_uri'] = $this->shimTool->target_link_uri;
        // now the only way to identify the target tool is via the client_id
        $goodParams['client_id'] = $this->platformClient->client_id;
        $resp = $this->call('get', $this->baseUrl, $goodParams);
        $resp->assertStatus(Response::HTTP_OK);
        $resp->assertViewHas('response.client_id',
                             $this->platformClient->client_id);
    }

    public function testNonExistentClientId()
    {
        $badParams = $this->goodParams;
        // purposely cripple the target_link_uri so that it cannot be used to
        // identify the target tool.
        $badParams['target_link_uri'] = $this->shimTool->target_link_uri;
        // now the only way to identify the target tool is via the client_id
        $badParams['client_id'] = $this->platformClient->client_id . 'aaa';
        $resp = $this->call('get', $this->baseUrl, $badParams);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testBadTargetLinkUri()
    {
        $badParams = $this->goodParams;
        // purposely cripple the target_link_uri so that it cannot be used to
        // identify the target tool.
        $badParams['target_link_uri'] = $this->shimTool->target_link_uri;
        $resp = $this->call('get', $this->baseUrl, $badParams);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testNonExistentTargetLinkUri()
    {
        $badParams = $this->goodParams;
        // purposely cripple the target_link_uri so that it cannot be used to
        // identify the target tool.
        $badParams['target_link_uri'] .= '999';
        $resp = $this->call('get', $this->baseUrl, $badParams);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }
}
