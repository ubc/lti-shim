<?php
namespace Tests\Features\LTI\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
 
use Jose\Easy\Load;

use Tests\TestCase;

use App\Models\Tool;
use App\Models\Platform;

use UBC\LTI\LTIException;
use UBC\LTI\Specs\Security\AccessToken;

class AccessTokenTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    private const EXPECTED_ACCESS_TOKEN = 'SomeExpectedAccessToken';

    private Platform $platform;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->platform = Platform::find(3);
        Http::fake([
            $this->platform->oauth_token_url =>  Http::response([
                'access_token' => self::EXPECTED_ACCESS_TOKEN
            ])
        ]);
    }

    public function testRequestAccessToken()
    {
        $scopes = ['https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly'];
        $actualAccessToken = AccessToken::request($this->platform, $scopes);
        $this->assertEquals(self::EXPECTED_ACCESS_TOKEN, $actualAccessToken);
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
        $this->expectException(LTIException::class);
        $actualAccessToken = AccessToken::request($this->platform, []);
    }

    private function validateRequestJwt($token)
    {
        $ownTool = Tool::getOwnTool();
        $key = $ownTool->keys()->first();

        // TODO: validate JTI?
        $jwt = Load::jws($token)
            ->algs(['RS256']) // The algorithms allowed to be used
            ->exp() // We check the "exp" claim
            ->iat(1000) // We check the "iat" claim. Leeway is 1000ms (1s)
            ->aud($this->platform->oauth_token_url) // Allowed audience
            ->iss($ownTool->iss) // Allowed issuer
            ->key($key->key) // Key used to verify the signature
            ->run(); // Go!
        $this->assertNotEmpty($jwt);
    }
}

