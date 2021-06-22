<?php

namespace Tests\Feature\LTI\Launch;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

use Symfony\Component\HttpFoundation\Response;

use Jose\Easy\Build;
use Jose\Easy\JWT;
use Jose\Easy\Load;

use Tests\Feature\LTI\LtiBasicTestCase;

use Database\Seeders\BasicTestDatabaseSeeder;

use App\Models\Ags;
use App\Models\AgsLineitem;
use App\Models\DeepLink;
use App\Models\LtiSession;
use App\Models\Nrps;
use App\Models\Platform;
use App\Models\ReturnUrl;

use UBC\LTI\Specs\Security\Nonce;

// tests last stage of launch, mainly the AuthRespHandler
class AuthRespTest extends LtiBasicTestCase
{
    private const CLAIM_AGS_URI = 'https://purl.imsglobal.org/spec/lti-ags/claim/endpoint';
    private const CLAIM_NRPS_URI = 'https://purl.imsglobal.org/spec/lti-nrps/claim/namesroleservice';
    private const CLAIM_LPRESENT_URI = 'https://purl.imsglobal.org/spec/lti/claim/launch_presentation';
    private const CLAIM_DL_URI = 'https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings';
    private const CLAIM_GM_URI = 'https://purl.imsglobal.org/spec/lti/claim/for_user';
    private const CLAIM_MESSAGE_TYPE_URI = 'https://purl.imsglobal.org/spec/lti/claim/message_type';
    private const TOOL_NONCE = 'SomeNonceFromTargetTool';
    private const TOOL_STATE = 'SomeFakeStateFromTargetTool';
    private const RESOURCE_LINK_ID = 'SomeResourceLinkId';
    // hardcoded as a check that the router is using the urls we expect
    private string $authUrl = '/lti/launch/redirect';
    private array $basicAuthParams = [];
    private array $expectedRoles =
        ['http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor'];

    protected function setUp(): void
    {
        parent::setUp();

        // the LtiSession at this stage wouldn't have all fields populated
        $this->ltiSession->deployment_id = null;
        $this->ltiSession->course_context_id = null;
        $this->ltiSession->lti_real_user_id = null;
        $this->ltiSession->state = [
            'sessionType' => 'regular',
            'redirect_uri' => route('lti.launch.redirect'),
            'nonce' => self::TOOL_NONCE
        ];
        $this->ltiSession->save();

        $this->basicAuthParams = [
            'state' => $this->ltiSession->createEncryptedId(),
            'id_token' => $this->createIdToken(Nonce::create())
        ];
    }

    /**
     * Create an id_token to send as part of the auth resp to the shim (we're
     * pretending that this is an auth resp from the originating platform).
     */
    private function createIdToken(
        string $nonce,
        array $claims=[], // additional claims to add into id_token
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
            ->iss($this->platform->iss)
            ->aud($this->platformClient->client_id)
            ->sub($this->realUser->sub)
            ->claim('nonce', $nonce)
            ->claim(self::CLAIM_MESSAGE_TYPE_URI,
                    'LtiResourceLinkRequest')
            ->claim('https://purl.imsglobal.org/spec/lti/claim/version',
                    '1.3.0')
            ->claim('https://purl.imsglobal.org/spec/lti/claim/deployment_id',
                    $this->deployment->lti_deployment_id)
            ->claim('https://purl.imsglobal.org/spec/lti/claim/target_link_uri',
                    route('lti.launch.midway'))
            ->claim('https://purl.imsglobal.org/spec/lti/claim/resource_link',
                    ['id' => self::RESOURCE_LINK_ID])
            ->claim('https://purl.imsglobal.org/spec/lti/claim/roles',
                    $this->expectedRoles)
            ->claim('https://purl.imsglobal.org/spec/lti/claim/custom',
                    ['target_tool_id' => $this->tool->id])
            ->claim('https://purl.imsglobal.org/spec/lti/claim/context',
                    ['id' => $this->courseContext->real_context_id])
            ->claim('name', $this->realUser->name)
            ->claim('email', $this->realUser->email);
        foreach ($claims as $key => $val) {
            $jws->claim($key, $val);
        }
        return $jws->sign($this->platform->getKey()->key);
    }

    /**
     * For decoding the id_token that we get back as the auth resp that we're
     * supposed to send to the target tool.
     */
    private function verifyAndGetJwt($token)
    {
        $key = $this->shimPlatform->getKey();

        return Load::jws($token)
            ->algs(['RS256'])
            ->exp()
            ->iat(2000)
            ->nbf()
            ->aud($this->tool->client_id)
            ->iss(config('lti.iss'))
            ->key($key->public_key)
            ->run();
    }

    /**
     * Test a minimal launch sent using a POST request.
     */
    public function testMinimalAuthResp()
    {
        $this->assertEquals(0, $this->realUser->lti_fake_users()->count());

        // call the shim results endpoint
        $resp = $this->post($this->authUrl, $this->basicAuthParams);
        $resp->assertStatus(Response::HTTP_OK);

        $this->checkSuccessfulResponse($resp);
    }

    /**
     * If the target tool left us a state, it should have been persisted in
     * LtiSession. So if such a state is there, we should see it passed back
     * in the auth resp.
     */
    public function testStatePassthrough()
    {
        $this->ltiSession->state += ['state' => self::TOOL_STATE];
        $this->ltiSession->save();

        $resp = $this->post($this->authUrl, $this->basicAuthParams);
        $resp->assertStatus(Response::HTTP_OK);
        $this->checkSuccessfulResponse($resp);
    }

    /**
     * Test that missing required params returns an error
     */
    public function testMissingRequiredParams()
    {
        $resp = $this->post($this->authUrl);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);

        $params = $this->basicAuthParams;
        unset($params['state']);
        $resp = $this->post($this->authUrl, $params);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);

        $params = $this->basicAuthParams;
        unset($params['id_token']);
        $resp = $this->post($this->authUrl, $params);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test that LTI service NRPS claims are properly filtered
     */
    public function testNrpsClaimFiltering()
    {
        // add an NRPS claim
        $extraClaims = [
            self::CLAIM_NRPS_URI => ["context_memberships_url" =>
                "https://ubc.test.instructure.com/api/lti/courses/9999999999/names_and_roles"]
        ];
        $params = $this->basicAuthParams;
        $params['id_token'] = $this->createIdToken(Nonce::create(),
                                                   $extraClaims);

        // there should be no NRPS entries yet
        $this->assertEquals(0, Nrps::count());

        $resp = $this->post($this->authUrl, $params);
        $resp->assertStatus(Response::HTTP_OK);
        $this->checkSuccessfulResponse($resp, $extraClaims);
    }

    /**
     * Test that LTI service AGS claims are properly filtered
     */
    public function testAgsClaimFiltering()
    {
        $extraClaims = [self::CLAIM_AGS_URI =>[
            'scope' => [
                'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem',
                'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly',
                'https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly',
                'https://purl.imsglobal.org/spec/lti-ags/scope/score'
            ],
            'lineitems' => 'https://something.example.com/ags/1/lineitems'
        ]];
        $addedClaims = $extraClaims;
        // this extra scope should be removed by filter
        array_push($addedClaims[self::CLAIM_AGS_URI]['scope'],
                   'https://purl.imsglobal.org/spec/lti-ags/scope/removeme');
        $params = $this->basicAuthParams;
        $params['id_token'] = $this->createIdToken(Nonce::create(),
                                                   $addedClaims);

        $this->assertEquals(0, Ags::count());

        $resp = $this->post($this->authUrl, $params);
        $resp->assertStatus(Response::HTTP_OK);
        $this->checkSuccessfulResponse($resp, $extraClaims);
    }

    /**
     * While a lineitems is required, an optional lineitem can also be present
     * if the launch refers to a single assignment. Make sure we properly
     * filter this too.
     */
    public function testAgsClaimWithLineitemFiltering()
    {
        $extraClaims = [self::CLAIM_AGS_URI => [
            'scope' => [
                'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem',
                'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly',
                'https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly',
                'https://purl.imsglobal.org/spec/lti-ags/scope/score'
            ],
            'lineitems' => 'https://something.example.com/ags/1/lineitems',
            'lineitem' => 'https://something.example.com/ags/1/lineitems/5',
        ]];
        $addedClaims = $extraClaims;
        // this extra scope should be removed by filter
        array_push($addedClaims[self::CLAIM_AGS_URI]['scope'],
                   'https://purl.imsglobal.org/spec/lti-ags/scope/removeme');
        $params = $this->basicAuthParams;
        $params['id_token'] = $this->createIdToken(Nonce::create(),
                                                   $addedClaims);

        $this->assertEquals(0, Ags::count());

        $resp = $this->post($this->authUrl, $params);
        $resp->assertStatus(Response::HTTP_OK);
        $this->checkSuccessfulResponse($resp, $extraClaims);
    }

    /**
     * The launch presentation claim could contain a return URL.  We have to
     * make sure that the return URL is converted to a shim URL. Also,
     * unrecognized params should be dropped.
     */
    public function testLaunchPresentationFiltering()
    {
        $extraClaims = [self::CLAIM_LPRESENT_URI => [
            'document_target' => 'iframe',
            'height' => '800',
            'width' => '600',
            'locale' => 'en-CA',
            'return_url' => 'https://example.com/lti/return_url/1?blah=abc'
        ]];
        $addedClaims = $extraClaims;
        $addedClaims[self::CLAIM_LPRESENT_URI]['unknownKey'] = 'removeMe';

        $params = $this->basicAuthParams;
        $params['id_token'] = $this->createIdToken(Nonce::create(),
                                                   $addedClaims);

        $this->assertEquals(0, ReturnUrl::count());

        $resp = $this->post($this->authUrl, $params);
        $resp->assertStatus(Response::HTTP_OK);
        $this->checkSuccessfulResponse($resp, $extraClaims);
    }

    /**
     * Test that LTI service Deep Link claims are properly filtered
     */
    public function testDeepLinkClaimFiltering()
    {
        // add an NRPS claim
        $extraClaims = [
            self::CLAIM_DL_URI => [
                'deep_link_return_url' =>
                    'https://platform.example.com/deep_links',
                'accept_types' => ['link', 'ltiResourceLink', 'image'],
                'accept_presentation_document_targets' =>
                    ['iframe', 'window', 'embed'],
                'accept_media_types' => 'image/:::asterisk:::,text/html',
                'accept_multiple' => true,
                'auto_create' => true,
                'title' => 'Some Deep Link Title',
                'text' => 'Some Deep Link Text',
                'data' => 'Some Deep Link Opaque Platform State'
            ],
            self::CLAIM_MESSAGE_TYPE_URI => 'LtiDeepLinkingRequest'
        ];
        // if message type is a regular launch, what do we do about
        // dl claim?
        $addedClaims = $extraClaims;
        $addedClaims[self::CLAIM_DL_URI]['invalid_dl_claim'] = 'removeMe';
        $params = $this->basicAuthParams;
        $params['id_token'] = $this->createIdToken(Nonce::create(),
                                                   $addedClaims);
        // there should be no Deep Link entries yet
        $this->assertEquals(0, DeepLink::count());

        $resp = $this->post($this->authUrl, $params);
        $resp->assertStatus(Response::HTTP_OK);
        $this->checkSuccessfulResponse($resp, $extraClaims);
    }

    /**
     * Test that Deep Link claims are dropped if message type isn't deep link.
     */
    public function testDeepLinkClaimDroppedIfNotDeepLinkMessage()
    {
        // add an NRPS claim
        $extraClaims = [
            self::CLAIM_DL_URI => [
                'deep_link_return_url' =>
                    'https://platform.example.com/deep_links',
                'accept_types' => ['link', 'ltiResourceLink', 'image'],
                'accept_presentation_document_targets' =>
                    ['iframe', 'window', 'embed']
            ]
        ];
        $params = $this->basicAuthParams;
        $params['id_token'] = $this->createIdToken(Nonce::create(),
                                                   $extraClaims);

        $resp = $this->post($this->authUrl, $params);
        $resp->assertStatus(Response::HTTP_OK);

        $jwt = $this->verifyAndGetJwt($resp['params']['id_token']);
        $this->assertFalse($jwt->claims->has(self::CLAIM_DL_URI));
    }

    /**
     * Test that gradebook message get properly filtered
     */
    public function testGradebookMessageClaimFiltering()
    {
        $extraClaims = [
            self::CLAIM_GM_URI => [
                'user_id' => $this->realUser->sub
            ],
            self::CLAIM_MESSAGE_TYPE_URI => 'LtiSubmissionReviewRequest'
        ];
        $params = $this->basicAuthParams;
        $params['id_token'] = $this->createIdToken(Nonce::create(),
                                                   $extraClaims);

        $resp = $this->post($this->authUrl, $params);
        $resp->assertStatus(Response::HTTP_OK);
        $this->checkSuccessfulResponse($resp, $extraClaims);
    }

    /**
     * Check that the returned auth resp has all the expected values
     */
    private function checkSuccessfulResponse(
        TestResponse $resp,
        array $extraClaims = []
    ) {
        $this->assertTrue(isset($resp['params']['id_token']));
        $this->assertTrue(isset($resp['formUrl']));
        // Check state is passed if we were given one.
        if (isset($this->ltiSession->state['state'])) {
            $this->assertTrue(isset($resp['params']['state']));
            $this->assertEquals($this->ltiSession->state['state'],
                                $resp['params']['state']);
        }
        $this->assertEquals(route('lti.launch.midway'),
                            $resp['formUrl']);

        // should've created a fake user entry for the real user
        $this->assertEquals(1, $this->realUser->lti_fake_users()->count());
        $fakeUser = $this->realUser->lti_fake_users()->first();

        $jwt = $this->verifyAndGetJwt($resp['params']['id_token']);

        // test required params
        $this->assertEquals(self::TOOL_NONCE, $jwt->claims->get('nonce'));
        $key = $this->shimPlatform->getKey();
        $this->assertEquals($key->kid, $jwt->header->get('kid'));
        $this->assertEquals('JWT', $jwt->header->get('typ'));

        $expectedMessageType = 'LtiResourceLinkRequest';
        if (isset($extraClaims[self::CLAIM_MESSAGE_TYPE_URI])) {
            // we've overridden the message type, so should expect that
            $expectedMessageType = $extraClaims[self::CLAIM_MESSAGE_TYPE_URI];
        }
        $this->assertEquals($expectedMessageType,
            $jwt->claims->get(self::CLAIM_MESSAGE_TYPE_URI));

        $this->assertEquals(
            '1.3.0',
            $jwt->claims->get(
                'https://purl.imsglobal.org/spec/lti/claim/version')
        );
        $this->deployment->refresh(); // reload fake_lti_deployment_id value
        $this->assertEquals(
            $this->deployment->fake_lti_deployment_id,
            $jwt->claims->get(
                'https://purl.imsglobal.org/spec/lti/claim/deployment_id')
        );
        $this->assertEquals(
            $this->tool->target_link_uri,
            $jwt->claims->get(
                'https://purl.imsglobal.org/spec/lti/claim/target_link_uri')
        );
        $this->assertEquals(
            self::RESOURCE_LINK_ID,
            $jwt->claims->get(
                'https://purl.imsglobal.org/spec/lti/claim/resource_link')['id']
        );
        $this->assertNotNull(
            $jwt->claims->get(
                'https://purl.imsglobal.org/spec/lti/claim/roles')
        );
        $this->assertEquals(
            $this->expectedRoles,
            $jwt->claims->get('https://purl.imsglobal.org/spec/lti/claim/roles')
        );

        // test optional params
        $this->courseContext->refresh();
        $this->assertEquals(
            $this->courseContext->fake_context_id,
            $jwt->claims->get(
                'https://purl.imsglobal.org/spec/lti/claim/context')['id']
        );

        // test user filter
        $this->assertEquals($fakeUser->name, $jwt->claims->get('name'));
        $this->assertEquals($fakeUser->first_name,
                            $jwt->claims->get('given_name'));
        $this->assertEquals($fakeUser->last_name,
                            $jwt->claims->get('family_name'));
        $this->assertEquals($fakeUser->email, $jwt->claims->get('email'));

        // test 'extra' claims, mainly lti service claims that we need to filter
        //
        // NRPS check
        if (isset($extraClaims[self::CLAIM_NRPS_URI]))
            $this->checkSuccessfulNrps($jwt, $extraClaims);
        else
            $this->assertFalse( $jwt->claims->has(self::CLAIM_NRPS_URI));

        // AGS check
        if (isset($extraClaims[self::CLAIM_AGS_URI]))
            $this->checkSuccessfulAgs($jwt, $extraClaims);
        else
            $this->assertFalse( $jwt->claims->has(self::CLAIM_AGS_URI));

        // Launch Presentation
        if (isset($extraClaims[self::CLAIM_LPRESENT_URI]))
            $this->checkSuccessfulLaunchPresent($jwt, $extraClaims);
        else
            $this->assertFalse( $jwt->claims->has(self::CLAIM_LPRESENT_URI));

        // Deep Link
        if (isset($extraClaims[self::CLAIM_DL_URI]))
            $this->checkSuccessfulDeepLink($jwt, $extraClaims);

        // Deep Link
        if (isset($extraClaims[self::CLAIM_GM_URI]))
            $this->checkSuccessfulGradebookMsg($jwt, $extraClaims);
    }

    /**
     * Assuming a successful launch with NRPS claims, check that the NRPS claim
     * values match what we expect.
     */
    private function checkSuccessfulNrps(JWT $jwt, array $extraClaims)
    {
        $this->assertTrue( $jwt->claims->has(self::CLAIM_NRPS_URI));
        $nrps = Nrps::first();
        $this->assertNotNull($nrps);
        // check that original claim is saved
        $this->assertEquals(
            $extraClaims[self::CLAIM_NRPS_URI]['context_memberships_url'],
            $nrps->context_memberships_url
        );
        // check that we hand out shim based urls instead of the original
        $this->assertNotEquals($nrps->context_memberships_url,
            $nrps->getShimUrl());
        $this->assertTrue(isset(
            $jwt->claims->get(self::CLAIM_NRPS_URI)['context_memberships_url']
        ));
        $this->assertEquals(
            $jwt->claims->get(self::CLAIM_NRPS_URI)['context_memberships_url'],
            $nrps->getShimUrl()
        );
    }

    /**
     * Assuming a successful launch with AGS claims, check that the AGS claim
     * values match what we expect.
     */
    private function checkSuccessfulAgs(JWT $jwt, array $extraClaims)
    {
        $this->assertTrue( $jwt->claims->has(self::CLAIM_AGS_URI));
        // an entry should now be in the ags table
        $ags = Ags::first();
        $this->assertNotNull($ags);

        // original claim should be saved in ags
        $this->assertEquals(
            $extraClaims[self::CLAIM_AGS_URI]['lineitems'],
            $ags->lineitems
        );

        // unrecognized scopes should be removed but otherwise passed
        // through
        $this->assertEquals(
            $extraClaims[self::CLAIM_AGS_URI]['scope'],
            $jwt->claims->get(self::CLAIM_AGS_URI)['scope']
        );
        $this->assertEquals(
            $extraClaims[self::CLAIM_AGS_URI]['scope'],
            $ags->scopes
        );

        // make sure the lineitems url now points to the shim ags
        $this->assertEquals(
            $ags->getShimLineitemsUrl(),
            $jwt->claims->get(self::CLAIM_AGS_URI)['lineitems']
        );

        // lineitem is optional and creates a separate AgsLineitem entry
        if (isset($extraClaims[self::CLAIM_AGS_URI]['lineitem'])) {
            $agsLineitem = AgsLineitem::first();
            $this->assertNotNull($agsLineitem);
            $this->assertEquals(
                $extraClaims[self::CLAIM_AGS_URI]['lineitem'],
                $agsLineitem->lineitem
            );
            $this->assertEquals(
                $agsLineitem->getShimLineitemUrl(),
                $jwt->claims->get(self::CLAIM_AGS_URI)['lineitem']
            );
        }
        else {
            $this->assertEmpty($ags->lineitem);
            $this->assertArrayNotHasKey('lineitem',
                $jwt->claims->get(self::CLAIM_AGS_URI));
        }
    }


    /**
     * Assuming a successful launch with Launch Presentation claims, check that
     * the Launch Presentation claim values match what we expect.
     */
    private function checkSuccessfulLaunchPresent(JWT $jwt, array $extraClaims)
    {
        $this->assertTrue( $jwt->claims->has(self::CLAIM_LPRESENT_URI));
        $returnUrl = ReturnUrl::first();
        $this->assertNotNull($returnUrl);
        // make sure original claim was saved
        $this->assertEquals(
            $extraClaims[self::CLAIM_LPRESENT_URI]['return_url'],
            $returnUrl->url
        );
        // make sure we got the filtered results
        $expectedLPresent = $extraClaims[self::CLAIM_LPRESENT_URI];
        $expectedLPresent['return_url'] = $returnUrl->getShimUrl();
        $this->assertEquals(
            $expectedLPresent,
            $jwt->claims->get(self::CLAIM_LPRESENT_URI)
        );
    }

    /**
     * Assuming a successful launch with Deep Linking claims, check that the
     * Deep Linking claim values match what we expect.
     */
    private function checkSuccessfulDeepLink(JWT $jwt, array $extraClaims)
    {
        // Deep Link requests should use a different message type
        $this->assertEquals('LtiDeepLinkingRequest',
            $jwt->claims->get(self::CLAIM_MESSAGE_TYPE_URI));

        $actualDlClaims = $jwt->claims->get(self::CLAIM_DL_URI);
        $this->assertNotNull($actualDlClaims);
        $this->assertFalse(isset($actualDlClaims['invalid_dl_claim']));

        // an entry should now be in the ags table
        $this->assertEquals(1, DeepLink::count());
        $dl = DeepLink::first();
        $this->assertNotNull($dl);
        // make sure we've saved the data claim for use later
        $this->assertEquals($extraClaims[self::CLAIM_DL_URI]['data'],
            $dl->state);
        // make sure that we can retrieve the DeepLink entry from the state
        // we passed to the target tool
        $decodedDl = DeepLink::decodeEncryptedId($actualDlClaims['data']);
        $this->assertNotNull($decodedDl);
        $this->assertEquals($dl->id, $decodedDl->id);

        // -- required claims
        $this->assertEquals($dl->shim_return_url,
            $actualDlClaims['deep_link_return_url']);
        $this->assertEquals(
            $extraClaims[self::CLAIM_DL_URI]['accept_types'],
            $actualDlClaims['accept_types']
        );
        $this->assertEquals(
            $extraClaims[self::CLAIM_DL_URI]['accept_presentation_document_targets'],
            $actualDlClaims['accept_presentation_document_targets']
        );
        // -- optional claims
        if (isset($extraClaims[self::CLAIM_DL_URI]['accept_media_types'])) {
            $this->assertEquals(
                $extraClaims[self::CLAIM_DL_URI]['accept_media_types'],
                $actualDlClaims['accept_media_types']
            );
        }
        if (isset($extraClaims[self::CLAIM_DL_URI]['accept_multiple'])) {
            $this->assertEquals(
                $extraClaims[self::CLAIM_DL_URI]['accept_multiple'],
                $actualDlClaims['accept_multiple']
            );
        }
        if (isset($extraClaims[self::CLAIM_DL_URI]['auto_create'])) {
            $this->assertEquals(
                $extraClaims[self::CLAIM_DL_URI]['auto_create'],
                $actualDlClaims['auto_create']
            );
        }
        if (isset($extraClaims[self::CLAIM_DL_URI]['title'])) {
            $this->assertEquals($extraClaims[self::CLAIM_DL_URI]['title'],
                $actualDlClaims['title']);
        }
        if (isset($extraClaims[self::CLAIM_DL_URI]['text'])) {
            $this->assertEquals($extraClaims[self::CLAIM_DL_URI]['text'],
                $actualDlClaims['text']);
        }
    }

    /**
     * Assuming a successful launch with Gradebook Message claims, check that
     * the Gradebook Message claim values match what we expect.
     */
    private function checkSuccessfulGradebookMsg(JWT $jwt, array $extraClaims)
    {
        $this->assertEquals(
            'LtiSubmissionReviewRequest',
            $jwt->claims->get(self::CLAIM_MESSAGE_TYPE_URI)
        );
        $fakeUser = $this->realUser->lti_fake_users()->first();
        $this->assertEquals(
            [
                'user_id' => $fakeUser->sub,
                'name' => $fakeUser->name,
                'email' => $fakeUser->email
            ],
            $jwt->claims->get(self::CLAIM_GM_URI)
        );
    }

    /**
     * Test that params being set to invalid values return an error
     */
    public function testInvalidParams()
    {
        $params = $this->basicAuthParams;
        $params['state'] = 'ThisIsNowInvalid';
        $resp = $this->post($this->authUrl, $params);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);

        $params = $this->basicAuthParams;
        $params['id_token'] = 'ThisIsNowInvalid';
        $resp = $this->post($this->authUrl, $params);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test that if the message type claim indicates a message we don't support,
     * the request fails.
     */
    public function testRejectUnsupportedMessageType()
    {
        // override the message type
        $extraClaims = [
            self::CLAIM_MESSAGE_TYPE_URI => 'BadMessageType'
        ];
        $params = $this->basicAuthParams;
        $params['id_token'] = $this->createIdToken(Nonce::create(),
                                                   $extraClaims);
        $resp = $this->post($this->authUrl, $params);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }
    /**
     * Nonce uniqueness is enforced by the tool side. We need to make sure that
     * the nonce passed back to us from the platform in the id_token is valid.
     *
     * This test rejects a nonce that has (presumably) been replayed.
     */
    public function testRejectUsedNonce()
    {
        $nonce = Nonce::create();
        Nonce::used($nonce); // mark this nonce as used

        $params = $this->basicAuthParams;
        $params['id_token'] = $this->createIdToken($nonce);
        $resp = $this->post($this->authUrl, $params);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Check that if the nonce given in the id_token is expired, we properly
     * reject it.
     */
    public function testRejectExpiredNonce()
    {
        $nonce = Nonce::create();
        $params = $this->basicAuthParams;
        $params['id_token'] = $this->createIdToken($nonce);

        // set the nonce expiration to the past
        DB::table('cache_nonce')->update(['expiration' => time() - 5]);

        $resp = $this->post($this->authUrl, $params);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Check that if the id_token itself is expired, we properly reject it.
     */
    public function testRejectExpiredIdToken()
    {
        $nonce = Nonce::create();
        $params = $this->basicAuthParams;
        $params['id_token'] = $this->createIdToken($nonce, [], true);

        $resp = $this->post($this->authUrl, $params);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test that replay attack protection works.
     */
    public function testReplayedAuthRespFails()
    {
        // first call goes through fine
        $resp = $this->post($this->authUrl, $this->basicAuthParams);
        $resp->assertStatus(Response::HTTP_OK);
        // second call should fail
        $resp = $this->post($this->authUrl, $this->basicAuthParams);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test that midway lookup only session skips sending info required for a
     * real resp to the target tool
     */
    public function testMidwayLookupOnly()
    {
        $this->ltiSession->state = ['sessionType' => 'midwayLookupOnly'];
        $this->ltiSession->save();
        $resp = $this->post($this->authUrl, $this->basicAuthParams);
        $resp->assertStatus(Response::HTTP_OK);
        $resp->assertViewHas('formUrl', route('lti.launch.midway'));
        // an actual value for the id_token shouldn't have been generated
        $resp->assertViewMissing('params.id_token');
    }
}
