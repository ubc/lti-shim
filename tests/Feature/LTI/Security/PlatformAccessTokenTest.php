<?php

namespace Tests\Feature\LTI\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Symfony\Component\HttpFoundation\Response;

use Jose\Easy\Build;
use Jose\Easy\JWSBuilder;

use App\Models\EncryptionKey;
use App\Models\Tool;

use UBC\LTI\Specs\JwsUtil;
use UBC\LTI\Specs\Security\AccessToken;

use Tests\TestCase;

// Tests UBC\LTI\Specs\Security\PlatformAccessToken
// LTI service requests are authenticated by an OAuth2 access token. The OAuth2
// access token needs to be obtained in a separate call. This test is for the
// end point that gives out the OAuth2 access token.
class PlatformAccessTokenTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    private string $baseUrl = '/lti/security/platform/token';
    private string $scope = 'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly';
    private Tool $tool; // the tool that is requesting an access token
    private array $goodParams;

    protected function setUp(): void
    {
        parent::setUp();
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

    /**
     * Request JWT building has been split up so that I can customize the
     * timestamps (iat, exp, nbf) used for testing timestamp validation.
     */
    private function getRequestJwt(): string
    {
        $builder = $this->getRequestJwtBuilder();
        $builder = $builder
            ->iat() // automatically set issued at time
            ->exp(time() + 60);
        return $this->signJwtBuilder($builder);
    }

    private function getRequestJwtBuilder(): JWSBuilder
    {
        return Build::jws()
            ->typ('JWT')
            ->alg('RS256')
            ->iss($this->tool->client_id)
            ->sub($this->tool->client_id)
            // the audience is often just the token endpoint url
            ->aud(config('app.url') . $this->baseUrl)
            ->jti('JWT Token Identifier');
    }

    private function signJwtBuilder(JWSBuilder $builder): string
    {
        return $builder->sign($this->tool->keys()->first()->key);
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

    public function testExpiredRequestLeeway()
    {
        // just within the leeway
        $builder = $this->getRequestJwtBuilder();
        $builder = $builder
            ->iat()
            ->exp(time() - JwsUtil::TOKEN_LEEWAY + 1);

        $goodParams = $this->goodParams;
        $goodParams['client_assertion'] = $this->signJwtBuilder($builder);

        $resp = $this->post($this->baseUrl, $goodParams);
        $resp->assertStatus(Response::HTTP_OK);

        // just out of the leeway
        $builder = $this->getRequestJwtBuilder();
        $builder = $builder
            ->iat()
            ->exp(time() - JwsUtil::TOKEN_LEEWAY - 1);

        $badParams = $this->goodParams;
        $badParams['client_assertion'] = $this->signJwtBuilder($builder);

        $resp = $this->post($this->baseUrl, $badParams);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testIssuedAtRequestLeeway()
    {
        // just within the leeway
        $builder = $this->getRequestJwtBuilder();
        $builder = $builder
            ->iat(time() + JwsUtil::TOKEN_LEEWAY - 1)
            ->exp(time() + 60);

        $goodParams = $this->goodParams;
        $goodParams['client_assertion'] = $this->signJwtBuilder($builder);

        $resp = $this->post($this->baseUrl, $goodParams);
        $resp->assertStatus(Response::HTTP_OK);

        // just out of the leeway
        $builder = $this->getRequestJwtBuilder();
        $builder = $builder
            ->iat(time() + JwsUtil::TOKEN_LEEWAY + 1)
            ->exp(time() + 60);

        $badParams = $this->goodParams;
        $badParams['client_assertion'] = $this->signJwtBuilder($builder);

        $resp = $this->post($this->baseUrl, $badParams);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testRequestTooOld()
    {
        $builder = $this->getRequestJwtBuilder();
        $builder = $builder
            ->iat(time() - JwsUtil::TOKEN_OLD_AGE - 1)
            ->exp(time() + 60);

        $badParams = $this->goodParams;
        $badParams['client_assertion'] = $this->signJwtBuilder($builder);

        $resp = $this->post($this->baseUrl, $badParams);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testNotBeforeRequestLeeway()
    {
        // just in the leeway
        $builder = $this->getRequestJwtBuilder();
        $builder = $builder
            ->iat(time())
            ->exp(time() + JwsUtil::TOKEN_LEEWAY)
            ->nbf(time() + JwsUtil::TOKEN_LEEWAY - 1);

        $goodParams = $this->goodParams;
        $goodParams['client_assertion'] = $this->signJwtBuilder($builder);

        $resp = $this->post($this->baseUrl, $goodParams);
        $resp->assertStatus(Response::HTTP_OK);

        // just out of the leeway
        $builder = $this->getRequestJwtBuilder();
        $builder = $builder
            ->iat(time())
            ->exp(time() + JwsUtil::TOKEN_LEEWAY)
            ->nbf(time() + JwsUtil::TOKEN_LEEWAY + 1);

        $badParams = $this->goodParams;
        $badParams['client_assertion'] = $this->signJwtBuilder($builder);

        $resp = $this->post($this->baseUrl, $badParams);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testExpIsRequired()
    {
        $builder = $this->getRequestJwtBuilder();
        $builder = $builder
            ->iat();

        $badParams = $this->goodParams;
        $badParams['client_assertion'] = $this->signJwtBuilder($builder);

        $resp = $this->post($this->baseUrl, $badParams);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testIatIsRequired()
    {
        $builder = $this->getRequestJwtBuilder();
        $builder = $builder
            ->exp(time() + 60);

        $badParams = $this->goodParams;
        $badParams['client_assertion'] = $this->signJwtBuilder($builder);

        $resp = $this->post($this->baseUrl, $badParams);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    // TODO: test exp, iat, nbf that are floating point numbers
    // difficulty is that the JWT framework won't deal with that, and I really
    // don't want to implement JWT signing myself
}
