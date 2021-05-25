<?php

namespace Tests\Feature\LTI\DeepLink;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

use Symfony\Component\HttpFoundation\Response;

use Jose\Easy\Build;
use Jose\Easy\JWT;
use Jose\Easy\Load;

use Tests\Feature\LTI\LtiBasicTestCase;

use Database\Seeders\BasicTestDatabaseSeeder;

use App\Models\DeepLink;

use UBC\LTI\Specs\Security\Nonce;

// tests last stage of deep link, the return
class ReturnTest extends LtiBasicTestCase
{
    private const CLAIM_CONTENT_ITEMS_URI = 'https://purl.imsglobal.org/spec/lti-dl/claim/content_items';
    private const CLAIM_DATA_URI = 'https://purl.imsglobal.org/spec/lti-dl/claim/data';
    private const CLAIM_DEPLOYMENT_ID_URI = 'https://purl.imsglobal.org/spec/lti/claim/deployment_id';
    private const CLAIM_MESSAGE_TYPE_URI = 'https://purl.imsglobal.org/spec/lti/claim/message_type';
    private const CLAIM_VERSION_URI = 'https://purl.imsglobal.org/spec/lti/claim/version';
    private const RESOURCE_LINK_ID = 'SomeResourceLinkId';

    // expected values
    private const RETURN_URL = 'https://a.example.edu/lti/dl/return';
    private const RETURN_STATE = 'SomeDeepLinkStateFromOrigPlatform';
    private const CONTENT_ITEMS = ['thing1key' => 'thing1value'];

    private DeepLink $dl;

    // hardcoded as a check that the router is using the urls we expect
    private string $returnUrl = '/lti/launch/return/';
    private array $baseParams = []; // default data sent to post request
    private array $baseClaims = []; // default claims in jwt

    protected function setUp(): void
    {
        parent::setUp();

        // an existing deep link entry should already be present
        $this->dl = new DeepLink();
        $this->dl->return_url = self::RETURN_URL;
        $this->dl->deployment_id = $this->deployment->id;
        $this->dl->tool_id = $this->tool->id;
        $this->dl->save();

        // deployment doesn't come with a fake id from factory because we
        // want to test fake id creation in other tests, so we need to set it
        $this->deployment->fake_lti_deployment_id = 'SomeFakeDeploymentId';
        $this->deployment->save();

        $this->returnUrl = $this->returnUrl . $this->dl->id;

        $this->baseClaims = [
            self::CLAIM_MESSAGE_TYPE_URI => 'LtiDeepLinkingResponse',
            self::CLAIM_DATA_URI => $this->dl->createEncryptedId(),
            self::CLAIM_VERSION_URI => '1.3.0',
            self::CLAIM_DEPLOYMENT_ID_URI =>
                $this->deployment->fake_lti_deployment_id,
            self::CLAIM_CONTENT_ITEMS_URI => self::CONTENT_ITEMS
        ];
        $this->baseParams =
            ['JWT' => $this->createToken(Nonce::create(), $this->baseClaims)];
    }

    /**
     * Create an return JWT to send as part of the return to the shim (we're
     * pretending that this is a return from the target tool).
     */
    private function createToken(
        string $nonce,
        array $claims,
        bool $isExpired=false
    ): string {
        $time = time();
        if ($isExpired) {
            // while tokens should be valid for only 1 hour, there's a 1 minute
            // leeway allowed for expiration checks, so we allow another 60 sec
            $time -= 3661;
        }
        $jws = Build::jws()
            ->alg('RS256')
            ->iat($time)
            ->exp($time + 3600)
            ->aud($this->shimPlatform->iss)
            ->iss($this->tool->client_id)
            ->claim('nonce', $nonce);
        foreach ($claims as $key => $val) {
            $jws->claim($key, $val);
        }
        return $jws->sign($this->tool->getKey()->key);
    }

    /**
     * For decoding the id_token that we get back as the auth resp that we're
     * supposed to send to the target tool.
     */
    private function verifyAndGetJwt($token)
    {
        $key = $this->shimTool->getKey();

        return Load::jws($token)
            ->algs(['RS256'])
            ->exp()
            ->iat(2000)
            ->nbf()
            ->aud($this->platform->iss)
            ->iss($this->platformClient->client_id)
            ->key($key->public_key)
            ->run();
    }

    /**
     * Test a minimal launch sent using a POST request.
     */
    public function testMinimalAuthResp()
    {
        // call the shim results endpoint
        $resp = $this->post($this->returnUrl, $this->baseParams);
        $resp->assertStatus(Response::HTTP_OK);

        $this->checkSuccessfulResponse($resp, $this->baseClaims);
    }

    /**
     * If the target tool left us a state, it should have been persisted in
     * LtiSession. So if such a state is there, we should see it passed back
     * in the auth resp.
     */
    public function testStatePassthrough()
    {
        $this->dl->state = self::RETURN_STATE;
        $this->dl->save();

        $resp = $this->post($this->returnUrl, $this->baseParams);
        $resp->assertStatus(Response::HTTP_OK);

        $this->checkSuccessfulResponse($resp, $this->baseClaims);
    }

    /**
     * Test that missing required params returns an error
     */
    public function testMissingRequiredParams()
    {
        $resp = $this->post($this->returnUrl);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);

        $resp = $this->post($this->returnUrl, ['wrongParam' => 'blah']);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Check that the returned auth resp has all the expected values
     */
    public function checkSuccessfulResponse(
        TestResponse $resp,
        array $claims
    ) {
        $this->assertTrue(isset($resp['params']['JWT']));
        $this->assertTrue(isset($resp['formUrl']));
        $this->assertEquals($this->dl->return_url, $resp['formUrl']);

        $jwt = $this->verifyAndGetJwt($resp['params']['JWT']);

        // test required params
        $key = $this->shimTool->getKey();
        $this->assertEquals($key->kid, $jwt->header->get('kid'));
        $this->assertEquals('JWT', $jwt->header->get('typ'));

        $this->assertEquals('LtiDeepLinkingResponse',
            $jwt->claims->get(self::CLAIM_MESSAGE_TYPE_URI));
        $this->assertEquals('1.3.0',
            $jwt->claims->get(self::CLAIM_VERSION_URI));
        $this->assertEquals(
            $this->deployment->lti_deployment_id,
            $jwt->claims->get(self::CLAIM_DEPLOYMENT_ID_URI));
        $this->assertTrue($jwt->claims->has(self::CLAIM_CONTENT_ITEMS_URI));
        $this->assertEquals(self::CONTENT_ITEMS,
            $jwt->claims->get(self::CLAIM_CONTENT_ITEMS_URI));

        // test state passthrough
        if ($this->dl->state) {
            $this->assertEquals(self::RETURN_STATE,
                $jwt->claims->get(self::CLAIM_DATA_URI));
        }
    }

    /**
     * Test that if we don't have the data claim, we can't restore DeepLink
     * state and therefore should return an error.
     */
    public function testInvalidDeepLinkStateInReceivedJwt()
    {
        $claims = $this->baseClaims;
        $claims[self::CLAIM_DATA_URI] = 'InvalidDeepLinkState';
        $params = ['JWT' => $this->createToken(Nonce::create(), $claims)];
        $resp = $this->post($this->returnUrl, $params);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);

        unset($claims[self::CLAIM_DATA_URI]);
        $params = ['JWT' => $this->createToken(Nonce::create(), $claims)];
        $resp = $this->post($this->returnUrl, $params);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testMissingRequiredClaims()
    {
        $claims = $this->baseClaims;
        unset($claims[self::CLAIM_MESSAGE_TYPE_URI]);
        $params = ['JWT' => $this->createToken(Nonce::create(), $claims)];
        $resp = $this->post($this->returnUrl, $params);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);

        $claims = $this->baseClaims;
        unset($claims[self::CLAIM_VERSION_URI]);
        $params = ['JWT' => $this->createToken(Nonce::create(), $claims)];
        $resp = $this->post($this->returnUrl, $params);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);

        $claims = $this->baseClaims;
        unset($claims[self::CLAIM_DEPLOYMENT_ID_URI]);
        $params = ['JWT' => $this->createToken(Nonce::create(), $claims)];
        $resp = $this->post($this->returnUrl, $params);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);

        $claims = $this->baseClaims;
        unset($claims[self::CLAIM_CONTENT_ITEMS_URI]);
        $params = ['JWT' => $this->createToken(Nonce::create(), $claims)];
        $resp = $this->post($this->returnUrl, $params);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test that params being set to invalid values return an error
     */
    public function testInvalidParams()
    {
        $params = ['JWT' => 'DefinitelyInvalidJWT'];
        $resp = $this->post($this->returnUrl, $params);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Nonce is generated by enforced by the tool side. We need to make sure
     * that the nonce passed back to us in the id_token is valid.
     *
     * This test rejects a nonce that has (presumably) been replayed.
     */
    public function testRejectUsedNonce()
    {
        $nonce = Nonce::create();
        Nonce::used($nonce); // mark this nonce as used

        $params = ['JWT' => $this->createToken($nonce, $this->baseClaims)];
        $resp = $this->post($this->returnUrl, $params);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test that we properly mark nonce we've seen to be used. We manually
     * marked the nonce as used in testRejectUsedNonce(), so this test makes
     * sure that we're actually automatically marking seen nonces as used.
     */
    public function testMarkSeenNonceAsUsed()
    {
        // first call to mark the nonce as used
        $resp = $this->post($this->returnUrl, $this->baseParams);
        $resp->assertStatus(Response::HTTP_OK);
        // second call should fail
        $resp = $this->post($this->returnUrl, $this->baseParams);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Check that if the nonce given in the id_token is expired, we properly
     * reject it.
     */
    public function testRejectExpiredNonce()
    {
        $nonce = Nonce::create();
        $params = ['JWT' => $this->createToken($nonce, $this->baseClaims)];

        // set the nonce expiration to the past
        DB::table('cache_nonce')->update(['expiration' => time() - 5]);

        $resp = $this->post($this->returnUrl, $params);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Check that if the id_token itself is expired, we properly reject it.
     */
    public function testRejectExpiredIdToken()
    {
        $nonce = Nonce::create();
        $params =
            ['JWT' => $this->createToken($nonce, $this->baseClaims, true)];

        $resp = $this->post($this->returnUrl, $params);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }
}
