<?php

namespace Tests\Feature\LTI\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Symfony\Component\HttpFoundation\Response;

use Jose\Easy\Build;

use App\Models\EncryptionKey;
use App\Models\Tool;

use UBC\LTI\Specs\Security\AccessToken;

use Tests\TestCase;

// Tests UBC\LTI\Specs\Security\PlatformOAuthToken
// LTI service requests are authenticated by an OAuth2 access token. The OAuth2
// access token needs to be obtained in a separate call. This test is for the
// end point that gives out the OAuth2 access token.
class OAuthTokenTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    private string $baseUrl;
    private string $scope = 'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly';
    private Tool $tool; // the tool that is requesting an access token
    private array $goodParams;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseUrl = config('lti.platform_security_token_path');
        $this->tool = factory(Tool::class)->create();
        // we just need to make sure there's an encryption key in the database
        factory(EncryptionKey::class)->create();
        $this->goodParams = [
            'grant_type' => 'client_credentials',
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $this->getRequestJwt(),
            'scope' => $this->scope
        ];
    }

    private function getRequestJwt(string $jti='JWT Token Identifier'): string
    {
        return Build::jws()
            ->typ('JWT')
            ->alg('RS256')
            ->iss($this->tool->iss)
            ->sub($this->tool->client_id)
            // the audience is often just the token endpoint url
            ->aud(config('app.url') . $this->baseUrl)
            ->iat() // automatically set issued at time
            ->exp(time() + 60)
            ->jti('JWT Token Identifier')
            ->sign($this->tool->keys()->first()->key);
    }

    /**
     * Test a tool getting an OAuth2 access token from the shim for LTI
     * service requests.
     *
     * @return void
     */
    public function testShimProvidesToken()
    {
        $resp = $this->post($this->baseUrl, $this->goodParams);
        //$resp->dump();
        $resp->assertStatus(Response::HTTP_OK);
        $resp->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in',
            'scope'
        ]);
        $goodData = [
            'token_type' => 'bearer',
            'scope' => $this->scope
        ];
        $resp->assertJson($goodData);
        $token = $resp->getOriginalContent()['access_token'];
        // Maybe we should also have a separate implementation for verifying the
        // access token
        $this->assertNotEmpty(AccessToken::verify($token));
    }

    public function testInvalidGrantType()
    {
        $badParams = $this->goodParams;
        $badParams['grant_type'] .= 'a';
        $resp = $this->post($this->baseUrl, $badParams);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testInvalidClientAssertionType()
    {
        $badParams = $this->goodParams;
        $badParams['client_assertion_type'] .= 'a';
        $resp = $this->post($this->baseUrl, $badParams);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testInvalidClientAssertion()
    {
        $badParams = $this->goodParams;
        $badParams['client_assertion'] = 'a' . $badParams['client_assertion'];
        $resp = $this->post($this->baseUrl, $badParams);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testInvalidScope()
    {
        $badParams = $this->goodParams;
        $badParams['scope'] .= 'a';
        $resp = $this->post($this->baseUrl, $badParams);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testInvalidParams()
    {
        foreach ($this->goodParams as $key => $val) {
            $badParams = $this->goodParams;
            unset($badParams[$key]);
            $resp = $this->post($this->baseUrl, $badParams);
            $resp->assertStatus(Response::HTTP_BAD_REQUEST);
        }
    }

    public function testProtectAgainstReplay()
    {
        $resp = $this->post($this->baseUrl, $this->goodParams);
        $resp->assertStatus(Response::HTTP_OK);
        // making the exact same request again should get rejected
        $resp = $this->post($this->baseUrl, $this->goodParams);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }
}
