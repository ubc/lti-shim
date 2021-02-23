<?php

namespace Tests\Feature\LTI\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

use Jose\Easy\Load;

use App\Models\Platform;
use App\Models\PlatformClient;
use App\Models\Tool;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Specs\Security\AccessToken;

use Tests\TestCase;

class AccessTokenTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    private const EXPECTED_ACCESS_TOKEN = 'SomeExpectedAccessToken';

    private AccessToken $tokenHelper;
    private Platform $platform;
    private Tool $tool;
    private array $scopes = [
        'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly'
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->platform = Platform::where('iss',
            'https://canvas.test.instructure.com')->first(); // canvas test
        $this->tool = Tool::where('name', 'Ltijs Demo Server')->first();

        $ltiLog = new LtiLog('AccessTokenTest');
        $this->tokenHelper = new AccessToken($ltiLog);
        Http::fake([
            $this->platform->access_token_url =>  Http::response([
                'access_token' => self::EXPECTED_ACCESS_TOKEN,
                'expires_in' => 3600
            ])
        ]);
    }

    public function testRequestAccessToken()
    {
        $actualToken = $this->tokenHelper->request($this->platform, $this->tool, $this->scopes);
        $this->assertEquals(self::EXPECTED_ACCESS_TOKEN, $actualToken);
        Http::assertSent(function ($request) {
            // required to be a form post request
            $this->assertTrue($request->isForm());
            // required params
            $this->assertEquals($request['grant_type'], 'client_credentials');
            $this->assertEquals(
                $request['client_assertion_type'],
                'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'
            );
            $this->assertEquals(
                $request['scope'],
                'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly'
            );
            // validate JWT
            $this->validateRequestJwt($request['client_assertion']);
            return true;
        });
    }

    public function testRequestAccessTokenFailsWithEmptyScope()
    {
        $this->expectException(LtiException::class);
        $this->tokenHelper->request($this->platform, $this->tool, []);
    }

    public function testAccessTokenIsCached()
    {
        // this should store the token into cache
        $actualToken = $this->tokenHelper->request($this->platform, $this->tool,
                                            $this->scopes);
        $this->assertEquals(self::EXPECTED_ACCESS_TOKEN, $actualToken);
        // modify the token stored in cache
        $expectedToken = self::EXPECTED_ACCESS_TOKEN . "NowModified";
        $nonceResult = DB::table('cache_access_tokens')->update([
            'value' => serialize($expectedToken)
        ]);
        // this should retrieve the now modified token from cache
        $actualToken = $this->tokenHelper->request($this->platform, $this->tool,
                                            $this->scopes);
        $this->assertEquals($expectedToken, $actualToken);
    }

    public function testRefreshExpiredTokens()
    {
        // this should store the token into cache
        $actualToken = $this->tokenHelper->request($this->platform, $this->tool,
                                            $this->scopes);
        $this->assertEquals(self::EXPECTED_ACCESS_TOKEN, $actualToken);
        // expire the cached token and change its value
        $expectedToken = self::EXPECTED_ACCESS_TOKEN . "NowModified";
        $nonceResult = DB::table('cache_access_tokens')->update([
            'value' => serialize($expectedToken),
            'expiration' => time() - 5
        ]);
        // the token shouldn't the one from cache
        $actualToken = $this->tokenHelper->request($this->platform, $this->tool,
                                            $this->scopes);
        $this->assertEquals(self::EXPECTED_ACCESS_TOKEN, $actualToken);
        // change the cached token again, this time we expect it to get the
        // the cached token
        $nonceResult = DB::table('cache_access_tokens')->update([
            'value' => serialize($expectedToken),
        ]);
        $actualToken = $this->tokenHelper->request($this->platform, $this->tool,
                                            $this->scopes);
        $this->assertEquals($expectedToken, $actualToken);
    }

    public function testDontCacheShortLivedTokens()
    {
        // switch platform so we can fake a request with a shorter expiry
        $platform = Platform::where('iss',
            'https://lti-ri.imsglobal.org')->first();

        Http::fake([
            $platform->access_token_url =>  Http::response([
                'access_token' => self::EXPECTED_ACCESS_TOKEN,
                'expires_in' => AccessToken::MINIMUM_TOKEN_VALID_TIME - 1
            ])
        ]);
        // hopefully doesn't store the token into cache
        $actualToken = $this->tokenHelper->request($platform, $this->tool,
                                            $this->scopes);
        $this->assertEquals(self::EXPECTED_ACCESS_TOKEN, $actualToken);
        // modify all tokens stored in cache
        $expectedToken = self::EXPECTED_ACCESS_TOKEN . "NowModified";
        $nonceResult = DB::table('cache_access_tokens')->update([
            'value' => serialize($expectedToken)
        ]);
        // this should still retrieve the unmodified token
        $actualToken = $this->tokenHelper->request($platform, $this->tool,
                                            $this->scopes);
        $this->assertEquals(self::EXPECTED_ACCESS_TOKEN, $actualToken);
    }

    public function testExpirationNotANumber()
    {
        // switch platform so we can fake a request with a shorter expiry
        $platform = Platform::where('iss',
            'https://lti-ri.imsglobal.org')->first();
        Http::fake([
            $platform->access_token_url =>  Http::response([
                'access_token' => self::EXPECTED_ACCESS_TOKEN,
                'expires_in' => "Shouldn'tBeString"
            ])
        ]);
        $this->expectException(LtiException::class);
        // hopefully doesn't store the token into cache
        $this->tokenHelper->request($platform, $this->tool, $this->scopes);
    }

    public function testRejectUnregisteredTool()
    {
        $badTool = Tool::factory()->create();
        $this->expectException(LtiException::class);
        $this->tokenHelper->request($this->platform, $badTool, $this->scopes);
    }

    private function validateRequestJwt($token)
    {
        $shimTool = Tool::getOwnTool();
        $key = $shimTool->keys()->first();
        $platformClient = PlatformClient::firstWhere([
            'tool_id' => $this->tool->id,
            'platform_id' => $this->platform->id
        ]);

        $jwt = Load::jws($token)
            ->algs(['RS256']) // The algorithms allowed to be used
            ->exp() // We check the "exp" claim
            ->iat(1000) // We check the "iat" claim. Leeway is 1000ms (1s)
            ->aud($this->platform->access_token_url) // Allowed audience
            ->sub($platformClient->client_id)
            ->iss($platformClient->client_id) // Allowed issuer
            ->key($key->key) // Key used to verify the signature
            ->run(); // Go!
        $this->assertNotEmpty($jwt);
        // test that the jti was properly stored as a nonce
        $nonceResult = DB::table('cache_nonce')->first();
        $nonce = str_replace('lti_shim_cache', '', $nonceResult->key);
        $this->assertEquals($nonce, $jwt->claims->jti());
    }
}

