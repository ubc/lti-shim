<?php

namespace Tests\Feature\LTI;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Symfony\Component\HttpFoundation\Response;

use Jose\Easy\Build;

use App\Models\Tool;

use Tests\TestCase;

// LTI service requests are authenticated by an OAuth2 token. The OAuth2 token
// needs to be obtained in a separate call. This test is for the end point that
// gives out the OAuth2 token.
class PlatformOAuthTokenTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    /**
     * Test getting the OAuth2 token for LTI service requests.
     *
     * @return void
     */
    public function testGetToken()
    {
        $baseUrl = config('lti.platform_security_token_path');

        // the tool that is requesting this token
        $tool = factory(Tool::class)->create();

        $requestJwt = Build::jws()
            ->typ('JWT')
            ->alg('RS256')
            ->iss($tool->iss)
            ->sub($tool->client_id)
            // the audience is often just the token endpoint url
            ->aud(config('app.url') . $baseUrl)
            ->iat() // automatically set issued at time
            ->exp(time() + 60)
            ->jti('JWT Token Identifier')
            ->sign($tool->keys()->first()->key);

        $scope = 'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly';
        $goodParams = [
            'grant_type' => 'client_credentials',
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $requestJwt,
            'scope' => $scope
        ];
        $resp = $this->post($baseUrl, $goodParams);
        //$resp->dump();
        $resp->assertStatus(200);
        $resp->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in',
            'scope'
        ]);
        $goodData = [
            'token_type' => 'bearer',
            'scope' => $scope
        ];
        $resp->assertJson($goodData);

        // test incorrect params
        $badParams = $goodParams;
        $badParams['grant_type'] .= 'a';
        $resp = $this->post($baseUrl, $badParams);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);

        $badParams = $goodParams;
        $badParams['client_assertion_type'] .= 'a';
        $resp = $this->post($baseUrl, $badParams);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);

        $badParams = $goodParams;
        $badParams['client_assertion'] = 'a' . $badParams['client_assertion'];
        $resp = $this->post($baseUrl, $badParams);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);

        $badParams = $goodParams;
        $badParams['scope'] .= 'a';
        $resp = $this->post($baseUrl, $badParams);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);

        // test one missing param
        foreach ($goodParams as $key => $val) {
            $badParams = $goodParams;
            unset($badParams[$key]);
            $resp = $this->post($baseUrl, $badParams);
            $resp->assertStatus(Response::HTTP_BAD_REQUEST);
        }

    }
}
