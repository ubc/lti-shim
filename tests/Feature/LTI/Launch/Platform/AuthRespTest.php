<?php

namespace Tests\Feature\LTI\Launch\Platform;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Symfony\Component\HttpFoundation\Response;

use Jose\Easy\Build;
use Jose\Easy\Load;

use App\Models\Ags;
use App\Models\CourseContext;
use App\Models\Deployment;
use App\Models\EncryptionKey;
use App\Models\LtiSession;
use App\Models\LtiFakeUser;
use App\Models\LtiRealUser;
use App\Models\Nrps;
use App\Models\Platform;
use App\Models\ResourceLink;
use App\Models\Tool;

use Tests\TestCase;

// only tests the incoming requests for the platform, this is just the auth req
class AuthRespTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    private string $baseUrl = '/lti/launch/platform/auth';
    private string $ltiMessageHint;
    private string $nonce = "someNonceHere";

    private array $goodValues;

    private CourseContext $courseContext;
    private Deployment $deployment;
    private EncryptionKey $encryptionKey;
    private LtiFakeUser $fakeUser;
    private LtiRealUser $realUser;
    private LtiSession $ltiSession;
    private Platform $shimPlatform;
    private Platform $platform;
    private ResourceLink $resourceLink;
    private Tool $tool;

    protected function setUp(): void
    {
        parent::setUp();
        // set up a known good request
        $this->tool = factory(Tool::class)->create();
        $this->shimPlatform = factory(Platform::class)->create(['id' => 1]);
        $this->platform = factory(Platform::class)->create(['id' => 2]);
        $this->encryptionKey = factory(EncryptionKey::class)->create();
        $this->deployment = factory(Deployment::class)->create([
            'platform_id' => $this->shimPlatform->id
        ]);
        $this->realUser = factory(LtiRealUser::class)->create([
            'platform_id' => $this->platform->id
        ]);
        $this->courseContext = factory(CourseContext::class)->create([
            'deployment_id' => $this->deployment->id,
            'tool_id' => $this->tool->id
        ]);
        $this->fakeUser = factory(LtiFakeUser::class)->create([
            'lti_real_user_id' => $this->realUser->id,
            'course_context_id' => $this->courseContext->id,
            'tool_id' => $this->tool->id
        ]);
        $this->resourceLink = factory(ResourceLink::class)->create([
            'deployment_id' => $this->deployment->id
        ]);
        // prepare session
        $this->ltiSession = factory(LtiSession::class)->create([
            'token' => [
                'sub' => $this->realUser->sub,
                'https://purl.imsglobal.org/spec/lti/claim/roles' => [],
                'https://purl.imsglobal.org/spec/lti/claim/resource_link' =>
                    ['id' => $this->resourceLink->real_link_id],
                'https://purl.imsglobal.org/spec/lti/claim/context' =>
                    ['id' => $this->courseContext->real_context_id],
                'name' => $this->realUser->name,
                'email' => $this->realUser->email
            ],
            'lti_real_user_id' => $this->realUser->id,
            'course_context_id' => $this->courseContext->id,
            'tool_id' => $this->tool->id,
            'deployment_id' => $this->deployment->id,
        ]);
        // create an encrypted jwt to pass the LtiSession, passed as lti
        // message hint
        $time = time();
        $this->ltiMessageHint = Build::jwe()
            ->exp($time + 3600)
            ->iat($time)
            ->nbf($time)
            ->alg('RSA-OAEP-256')
            ->enc('A256GCM')
            ->claim('lti_session', $this->ltiSession->id)
            ->encrypt($this->encryptionKey->public_key);
        $this->goodValues = [
            'scope' => 'openid',
            'response_type' => 'id_token',
            'response_mode' => 'form_post',
            'login_hint' => $this->fakeUser->login_hint,
            'client_id' => $this->tool->client_id,
            'prompt' => 'none',
            'nonce' => $this->nonce,
            'lti_message_hint' => $this->ltiMessageHint
        ];
    }

    // we can add additional claims to $ltiSession to test the platform's
    // claim filtering ability
    private function addClaims(array $claims)
    {
        $this->ltiSession->token = array_merge($this->ltiSession->token,
            $claims);
        $this->ltiSession->save();
    }

    // reconstructing the id_token is a bit difficult, so we'll decode it
    // and check the claims instead of trying to make sure the JWT matches up
    private function getJwtFromResponse($response)
    {
        $token = $response->getOriginalContent()
                          ->getData()['response']['id_token'];
        $this->platformKey = $this->shimPlatform->keys()->first();
        $jwt = Load::jws($token)
            ->algs(['RS256'])
            ->exp()
            ->iat(2000)
            ->nbf()
            ->aud($this->tool->client_id)
            ->iss(config('lti.iss'))
            ->sub($this->fakeUser->sub)
            ->key($this->platformKey->public_key)
            ->run();
        return $jwt;
    }

    /**
     * If required LTI params are missing from the login, throw a 400 error.
     *
     * @return void
     */
    public function testStandardAuthResponse()
    {
        // check the static values first
        $response = $this->call('get', $this->baseUrl, $this->goodValues);
        $response->assertStatus(Response::HTTP_OK);
        // make sure where we send the response is right
        $response->assertViewHas('auth_resp_url',
                                 $this->shimPlatform->auth_resp_url);
        $jwt = $this->getJwtFromResponse($response);
        // no state in the values we sent, so state should be empty here
        $response->assertViewMissing('response.state');
        // test filters
        $this->assertEquals($this->fakeUser->name, $jwt->claims->get('name'));
        $this->assertEquals($this->fakeUser->first_name,
                            $jwt->claims->get('given_name'));
        $this->assertEquals($this->fakeUser->last_name,
                            $jwt->claims->get('family_name'));
        $this->assertEquals($this->fakeUser->email, $jwt->claims->get('email'));
        // test required params
        $this->assertEquals($this->nonce, $jwt->claims->get('nonce'));
        $this->assertEquals('JWT', $jwt->claims->get('typ'));
        $this->assertEquals($this->platformKey->kid, $jwt->claims->get('kid'));
        $this->assertEquals(
            'LtiResourceLinkRequest',
            $jwt->claims->get(
                'https://purl.imsglobal.org/spec/lti/claim/message_type')
        );
        $this->assertEquals(
            '1.3.0',
            $jwt->claims->get(
                'https://purl.imsglobal.org/spec/lti/claim/version')
        );
        $this->deployment = $this->deployment->fresh(); // reload fake_lti_deployment_id value
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
            $this->resourceLink->fake_link_id,
            $jwt->claims->get(
                'https://purl.imsglobal.org/spec/lti/claim/resource_link')['id']
        );
        $this->assertNotNull(
            $jwt->claims->get(
                'https://purl.imsglobal.org/spec/lti/claim/roles')
        );
        // test optional params
        $this->assertEquals(
            $this->courseContext->fake_context_id,
            $jwt->claims->get(
                'https://purl.imsglobal.org/spec/lti/claim/context')['id']
        );
        // check state is passed properly if included
        $state = 'someFakeState';
        $this->goodValues['state'] = $state;
        $response = $this->call('get', $this->baseUrl, $this->goodValues);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewHas('response.state', $state);
    }

    public function testStatePassthrough()
    {
        // add state the values we send
        $state = 'SomeStateThatShouldBePassedBackAsIs';
        $goodValues = $this->goodValues;
        $goodValues['state'] = $state;

        $response = $this->call('get', $this->baseUrl, $goodValues);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewHas('response.state', $state);
    }

    public function testNoNrpsClaim()
    {
        // if we don't have nrps claim in the session, none should be passed
        $response = $this->call('get', $this->baseUrl, $this->goodValues);
        $response->assertStatus(Response::HTTP_OK);
        // make sure where we send the response is right
        $response->assertViewHas('auth_resp_url',
                                 $this->shimPlatform->auth_resp_url);
        $jwt = $this->getJwtFromResponse($response);
        $this->assertFalse(
            $jwt->claims->has(
                'https://purl.imsglobal.org/spec/lti-nrps/claim/namesroleservice')
        );
    }

    public function testHasNrpsClaim()
    {
        $claimUri = 'https://purl.imsglobal.org/spec/lti-nrps/claim/namesroleservice';
        // add an nrps claim to the session
        $expectedNrpsUrl = "https://ubc.test.instructure.com/api/lti/courses/9999999999/names_and_roles";
        $this->addClaims([
            $claimUri => [ "context_memberships_url" => $expectedNrpsUrl ]
        ]);

        $response = $this->call('get', $this->baseUrl, $this->goodValues);
        $response->assertStatus(Response::HTTP_OK);
        // make sure where we send the response is right
        $response->assertViewHas('auth_resp_url',
                                 $this->shimPlatform->auth_resp_url);
        $jwt = $this->getJwtFromResponse($response);
        $this->assertTrue($jwt->claims->has($claimUri));
        // there should be corresponding entry in the nrps table
        $nrps = Nrps::first();
        $this->assertEquals($nrps->context_memberships_url, $expectedNrpsUrl);
        $this->assertNotEquals($nrps->context_memberships_url,
                               $nrps->getShimUrl());
        $this->assertEquals(
            $jwt->claims->get($claimUri)['context_memberships_url'],
            $nrps->getShimUrl()
        );
    }

    public function testNoAgsClaim()
    {
        // if we don't have nrps claim in the session, none should be passed
        $response = $this->call('get', $this->baseUrl, $this->goodValues);
        $response->assertStatus(Response::HTTP_OK);
        // make sure where we send the response is right
        $response->assertViewHas('auth_resp_url',
                                 $this->shimPlatform->auth_resp_url);
        $jwt = $this->getJwtFromResponse($response);
        $this->assertFalse(
            $jwt->claims->has(
                'https://purl.imsglobal.org/spec/lti-ags/claim/endpoint')
        );
    }

    public function testHasAgsClaim()
    {
        $claimUri = 'https://purl.imsglobal.org/spec/lti-ags/claim/endpoint';
        // add an nrps claim to the session
        $expectedScopes = [
            'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem',
            'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly',
            'https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly',
            'https://purl.imsglobal.org/spec/lti-ags/scope/score'
        ];
        $expectedLineitemsUrl = 'https://example.com/ags/1/lineitems';
        $this->addClaims([$claimUri =>
            [
                'scope' => array_merge($expectedScopes,
                    ['https://purl.imsglobal.org/spec/lti-ags/scope/removeme']),
                'lineitems' => $expectedLineitemsUrl
            ]
        ]);
        //fwrite(STDERR, "LTISESSION ID" . $this->ltiSession->id . "\n");

        $response = $this->call('get', $this->baseUrl, $this->goodValues);
        //$response->dump();
        $response->assertStatus(Response::HTTP_OK);
        // make sure where we send the response is right
        $response->assertViewHas('auth_resp_url',
                                 $this->shimPlatform->auth_resp_url);
        $jwt = $this->getJwtFromResponse($response);
        $this->assertTrue($jwt->claims->has($claimUri));
        // unrecognized scopes should be removed
        $this->assertEquals($expectedScopes,
            $jwt->claims->get($claimUri)['scope']);
        // there should be corresponding entry in the ags table
        $ags = Ags::first();
        $this->assertEquals($ags->lineitems, $expectedLineitemsUrl);
        $this->assertEquals($ags->scopes, $expectedScopes);
        $this->assertEmpty($ags->lineitem);
        // make sure the lineitems url was replaced
        $this->assertEquals(
            $jwt->claims->get($claimUri)['lineitems'],
            $ags->getShimLineitemsUrl()
        );
        $this->assertArrayNotHasKey('lineitem', $jwt->claims->get($claimUri));

        // test that the addition of lineitem gives us a new ags entry
        $expectedLineitemUrl = 'https://example.com/ags/1/lineitems/111';
        $this->addClaims([$claimUri =>
            [
                'scope' => $expectedScopes,
                'lineitems' => $expectedLineitemsUrl,
                'lineitem' => $expectedLineitemUrl
            ]
        ]);
        $response = $this->call('get', $this->baseUrl, $this->goodValues);
        $response->assertStatus(Response::HTTP_OK);
        $jwt = $this->getJwtFromResponse($response);
        $ags = Ags::find(2);
        $this->assertEquals($ags->lineitem, $expectedLineitemUrl);
        $this->assertEquals(
            $jwt->claims->get($claimUri)['lineitem'],
            $ags->getShimLineitemUrl()
        );
    }
}
