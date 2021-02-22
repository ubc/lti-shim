<?php

namespace Tests\Feature\LTI\Nrps;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

use Tests\TestCase;

use App\Models\CourseContext;
use App\Models\Deployment;
use App\Models\LtiRealUser;
use App\Models\Nrps;
use App\Models\Platform;
use App\Models\Tool;

use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Specs\Nrps\PlatformNrps;
use UBC\LTI\Specs\Security\AccessToken;

// tests the entire NRPS flow
class NrpsTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    private string $baseUrl = '/lti/nrps/platform/';
    private string $accessToken;
    private AccessToken $tokenHelper;
    private CourseContext $courseContext;
    private Deployment $deployment;
    private Nrps $nrps;
    private Platform $platform;
    private Tool $tool;

    private array $fakeNrps; // holds the fake NRPS response that the platform
                            // sends back, that needs to be filtered
    private array $headers; // headers sent on each NRPS request

    protected function setUp(): void
    {
        parent::setUp();
        // setup the database
        $this->seed();
        $this->tool = Tool::find(2);
        $this->platform = Platform::find(3); // canvas test
        $this->deployment = Deployment::factory()->create([
            'platform_id' => $this->platform->id
        ]);
        $this->courseContext = CourseContext::factory()->create([
            'deployment_id' => $this->deployment->id,
            'tool_id' => $this->tool->id
        ]);
        $this->nrps = Nrps::factory()->create([
            'course_context_id' => $this->courseContext->id,
            'deployment_id' => $this->deployment->id,
            'tool_id' => $this->tool->id
        ]);
        $ltiLog = new LtiLog('NrpsTest');
        $this->tokenHelper = new AccessToken($ltiLog);
        $this->accessToken = $this->tokenHelper->create(
            $this->tool,
            ['https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly']
        );
        $this->headers = [
            'Accept' => 'application/vnd.ims.lti-nrps.v2.membershipcontainer+json',
            'Authorization' => 'Bearer ' . $this->accessToken
        ];
        // configure fake http responses
        $this->fakeNrps = [
            "id" => "http://192.168.55.182:8900/api/lti/courses/1/names_and_roles",
            "context" => [
                "id" => $this->courseContext->real_context_id,
                "label" => "TEST100Label",
                "title" => "TEST100Title",
            ],
            "members" => [
                [
                    "status" => "Active",
                    "name" => "instructor1",
                    "picture" => "https://192.168.55.182:8900/images/messages/avatar-70.png",
                    "given_name" => "instructor1@example.com",
                    "family_name" => "",
                    "email" => "instructor1@example.com",
                    "user_id" => "00000000-0000-0000-0000-000000000000",
                    "lti11_legacy_user_id" => "0000000000000000000000000000000000000000",
                    "roles" => ["http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor"]
                ],
                [
                    "status" => "Active",
                    "name" => "student1",
                    "picture" => "https://192.168.55.182:8900/images/messages/avatar-50.png",
                    "given_name" => "student1",
                    "family_name" => "",
                    "email" => "student1@example.com",
                    "user_id" => "6969b2d7-e507-40e1-850a-b39b93a43eb5",
                    "lti11_legacy_user_id" => "86157096483e6b3a50bfedc6bac902c0b20a824f",
                    "roles" => ["http://purl.imsglobal.org/vocab/lis/v2/membership#Learner"]
                ]
            ]
        ];
    }

    private function setupHttpFake($fakeNrpsResponse = null)
    {
        // let us override the fake nrps info if needed
        if (!$fakeNrpsResponse)
            $fakeNrpsResponse = Http::response($this->fakeNrps);
        Http::fake([
            $this->nrps->context_memberships_url => $fakeNrpsResponse,
            $this->platform->access_token_url => Http::response([
                'access_token' => $this->accessToken,
                'expires_in' => 3600
            ])
        ]);
    }

    /**
     * If we try to access an NRPS endpoint on the shim that doesn't exist in
     * the database, we should get a 404
     *
     * @return void
     */
    public function testNonExistentNrpsEndpoint()
    {
        $this->setupHttpFake();
        $resp = $this->withHeaders($this->headers)->get($this->baseUrl . '9999');
        $resp->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test that the users returned by the NRPS request are entered into the
     * database and fake users created for them.
     */
    public function testContextAndMemberFiltering()
    {
        $this->setupHttpFake();
        // do the nrps call
        $resp = $this->withHeaders($this->headers)
                     ->get($this->nrps->getShimUrl());
        //$resp->dump();
        // request should be successful
        $resp->assertStatus(Response::HTTP_OK);
        // the response must have these fields
        $resp->assertJsonStructure([
            'id',
            'context',
            'members'
        ]);
        // make sure the the course has been filtered
        $expectedContext = $this->fakeNrps['context'];
        $expectedContext['id'] = $this->courseContext->fake_context_id;
        $actualContext = $resp['context'];
        $this->assertEquals($expectedContext, $actualContext);
        // make sure that the users we got back have been entered into database
        $this->assertNotEmpty($this->fakeNrps['members']); // sanity check, make sure
                                                    // we aren't skipping loop
        $expectedFakeUsers = []; // store fake users for filter verification
        foreach ($this->fakeNrps['members'] as $expectedRealUser) {
            $actualRealUser = LtiRealUser::firstWhere('sub',
                                                  $expectedRealUser['user_id']);
            $this->assertNotEmpty($actualRealUser);
            $this->assertEquals($expectedRealUser['name'],
                                $actualRealUser->name);
            $this->assertEquals($expectedRealUser['email'],
                                $actualRealUser->email);
            $this->assertEquals($this->platform->id,
                                $actualRealUser->platform_id);
            // make sure the real users also has a fake user created
            $fakeUser = $actualRealUser->lti_fake_users()->first();
            $this->assertNotEmpty($fakeUser);
            $this->assertEquals($this->tool->id, $fakeUser->tool_id);
            $expectedFakeUsers[$fakeUser->sub] = [
                'fakeUser' => $fakeUser,
                'roles' => $expectedRealUser['roles']
            ];
        }
        // make sure we got the filtered members result back
        $this->assertNotEmpty($resp['members']);
        foreach ($resp['members'] as $actualFakeUser) {
            $this->assertArrayHasKey($actualFakeUser['user_id'],
                                     $expectedFakeUsers);
            $expectedFakeUser =
                $expectedFakeUsers[$actualFakeUser['user_id']]['fakeUser'];
            $expectedRoles =
                $expectedFakeUsers[$actualFakeUser['user_id']]['roles'];
            $this->assertNotEmpty($expectedFakeUser);
            $this->assertEquals($expectedFakeUser->name,
                                $actualFakeUser['name']);
            $this->assertEquals($expectedFakeUser->first_name,
                                $actualFakeUser['given_name']);
            $this->assertEquals($expectedFakeUser->last_name,
                                $actualFakeUser['family_name']);
            $this->assertEquals($expectedFakeUser->email,
                                $actualFakeUser['email']);
            $this->assertEquals($expectedRoles,
                                $actualFakeUser['roles']);
        }
        // make sure that the course context title and label were updated
        $this->courseContext->refresh();
        $this->assertEquals($this->courseContext->title, $actualContext['title']);
        $this->assertEquals($this->courseContext->label, $actualContext['label']);
    }

    /**
     * Test that pagination and role filter queries are passed through as is.
     */
    public function testLimitAndRoleParamPassthrough()
    {
        $this->setupHttpFake();
        $expectedQueries = '?limit=1&role=Teacher';
        // the queries are passed on to the original URL, so we need to modify
        // it to match in the fake response
        $this->fakeNrps['id'] = $this->fakeNrps['id'] . $expectedQueries;
        // make sure we fake the HTTP response to the URL with the queries
        Http::fake([
            $this->nrps->context_memberships_url . $expectedQueries =>
                Http::response($this->fakeNrps)
        ]);
        $expectedUrl = $this->nrps->getShimUrl() . $expectedQueries;

        $resp = $this->withHeaders($this->headers)->get($expectedUrl);
        $resp->assertStatus(Response::HTTP_OK);
        // make sure that the NRPS url is rewritten with the params
        $actualUrl = $resp['id'];
        $this->assertEquals($expectedUrl, $actualUrl);
    }

    /**
     * Pagination URLs are passed using the "Link" header, we need to make sure
     * those URLs are rewritten to shim NRPS URLs too.
     */
    public function testPaginationHeaderFiltering()
    {
        $this->setupHttpFake();
        $nrps = Nrps::factory()->create([
            'course_context_id' => $this->courseContext->id,
            'deployment_id' => $this->deployment->id,
            'tool_id' => $this->tool->id
        ]);
        // make sure to send the link header in the fake response
        Http::fake([
            $nrps->context_memberships_url => Http::response(
                $this->fakeNrps,
                Response::HTTP_OK,
                [
                    'link' => '<http://192.168.55.182:8900/api/lti/courses/1/names_and_roles?page=1&per_page=1>; rel="current",<http://192.168.55.182:8900/api/lti/courses/1/names_and_roles?page=2&per_page=1>; rel="next",<http://192.168.55.182:8900/api/lti/courses/1/names_and_roles?page=1&per_page=1>; rel="first",<http://192.168.55.182:8900/api/lti/courses/1/names_and_roles?page=2&per_page=1>; rel="last"'
                ]
            ),
            '*' => Http::response('Accidental real request',
                                  Response::HTTP_NOT_FOUND)
        ]);
        $resp = $this->withHeaders($this->headers)->get($nrps->getShimurl());
        $resp->assertStatus(Response::HTTP_OK);
        // make sure the link urls were rewritten to shim URLs
        $resp->assertHeader('link',
            '<http://localhost/lti/nrps/platform/3>; rel="current",<http://localhost/lti/nrps/platform/4>; rel="next",<http://localhost/lti/nrps/platform/3>; rel="first",<http://localhost/lti/nrps/platform/4>; rel="last"');
        // make sure corresponding NRPS entries are in the database
        $actualNrps = Nrps::find(3);
        $this->assertNotEmpty($actualNrps);
        $this->assertEquals('http://localhost/lti/nrps/platform/3',
            $actualNrps->getShimUrl());
        $actualNrps = Nrps::find(4);
        $this->assertNotEmpty($actualNrps);
        $this->assertEquals('http://localhost/lti/nrps/platform/4',
            $actualNrps->getShimUrl());
    }

    /**
     * Make sure that NRPS calls will reject invalid access tokens.
     */
    public function testRejectInvalidAccessToken()
    {
        $this->setupHttpFake();
        // change the access token to a bad one
        $headers = $this->headers;
        $headers['Authorization'] = 'Bearer ClearlyBadAccessToken';

        // do the nrps call
        $resp = $this->withHeaders($headers)->get($this->nrps->getShimUrl());
        // request should fail
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testMissingAccessToken()
    {
        $this->setupHttpFake();
        // delete the access token
        $headers = $this->headers;
        unset($headers['Authorization']);
        // do the nrps call
        $resp = $this->withHeaders($headers)->get($this->nrps->getShimUrl());
        // request should fail
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testAuthorizationHeaderCaseSensitivity()
    {
        $this->setupHttpFake();
        $headers = $this->headers;
        // lower case b in bearer
        $headers['Authorization'] = 'bearer ' . $this->accessToken;
        $resp = $this->withHeaders($headers)->get($this->nrps->getShimUrl());
        $resp->assertStatus(Response::HTTP_OK);
        // lower case authorization
        unset($headers['Authorization']);
        $headers['authorization'] = 'Bearer ' . $this->accessToken;
        $resp = $this->withHeaders($headers)->get($this->nrps->getShimUrl());
        $resp->assertStatus(Response::HTTP_OK);
    }

    public function testRejectAccessTokenWithIncorrectScope()
    {
        $this->setupHttpFake();
        $accessToken = $this->tokenHelper->create(
            $this->tool,
            ['https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly']
        );
        // change the access token to one that doesn't have NRPS scope
        $headers = $this->headers;
        $headers['Authorization'] = $accessToken;

        // do the nrps call
        $resp = $this->withHeaders($headers)->get($this->nrps->getShimUrl());
        // request should fail
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testRejectAccessTokenWithIncorrectTool()
    {
        $this->setupHttpFake();
        $tool = Tool::find(3);
        $accessToken = $this->tokenHelper->create(
            $tool,
            ['https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly']
        );
        // change the access token to one that doesn't have NRPS scope
        $headers = $this->headers;
        $headers['Authorization'] = $accessToken;

        // do the nrps call
        $resp = $this->withHeaders($headers)->get($this->nrps->getShimUrl());
        // request should fail
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test that new users in a second NRPS call are added to the
     * database and fake users created for them.
     */
    public function testNewUsersAreAdded()
    {
        // set up our fake NRPS response so that our second NRPS call has a
        // new user
        $secondFakeNrps = $this->fakeNrps;
        $secondFakeNrps['members'][] = [
            "status" => "Active",
            "name" => "student2",
            "picture" => "https://192.168.55.182:8900/images/messages/avatar-07.png",
            "given_name" => "student2",
            "family_name" => "",
            "email" => "student2@example.com",
            "user_id" => "22222222-2222-2222-2222-222222222222",
            "lti11_legacy_user_id" => "2222222222222222222222222222222222222222",
            "roles" => ["http://purl.imsglobal.org/vocab/lis/v2/membership#Learner"]
        ];
        $fakeNrpsResponse = Http::sequence()
            ->push($this->fakeNrps, Response::HTTP_OK)
            ->push($secondFakeNrps);
        $this->setupHttpFake($fakeNrpsResponse);
        // do the first nrps call
        $resp = $this->withHeaders($this->headers)
                     ->get($this->nrps->getShimUrl());
        $resp->assertStatus(Response::HTTP_OK);
        // do the second nrps call
        $resp = $this->withHeaders($this->headers)
                     ->get($this->nrps->getShimUrl());
        //$resp->dump();
        $resp->assertStatus(Response::HTTP_OK);
        // make sure that the users we got back have been entered into database
        $this->assertNotEmpty($secondFakeNrps['members']); // sanity check, make
                                                // sure we aren't skipping loop
        $expectedFakeUsers = []; // store fake users for filter verification
        foreach ($secondFakeNrps['members'] as $expectedRealUser) {
            $actualRealUser = LtiRealUser::firstWhere('sub',
                                                  $expectedRealUser['user_id']);
            $this->assertNotEmpty($actualRealUser);
            $this->assertEquals($expectedRealUser['name'],
                                $actualRealUser->name);
            $this->assertEquals($expectedRealUser['email'],
                                $actualRealUser->email);
            $this->assertEquals($this->platform->id,
                                $actualRealUser->platform_id);
            // make sure the real users also has a fake user created
            $fakeUser = $actualRealUser->lti_fake_users()->first();
            $this->assertNotEmpty($fakeUser);
            $this->assertEquals($this->tool->id, $fakeUser->tool_id);
            $expectedFakeUsers[$fakeUser->sub] = [
                'fakeUser' => $fakeUser,
                'roles' => $expectedRealUser['roles']
            ];
        }
        // make sure we got the filtered members result back
        $this->assertNotEmpty($resp['members']);
        foreach ($resp['members'] as $actualFakeUser) {
            $this->assertArrayHasKey($actualFakeUser['user_id'],
                                     $expectedFakeUsers);
            $expectedFakeUser =
                $expectedFakeUsers[$actualFakeUser['user_id']]['fakeUser'];
            $expectedRoles =
                $expectedFakeUsers[$actualFakeUser['user_id']]['roles'];
            $this->assertNotEmpty($expectedFakeUser);
            $this->assertEquals($expectedFakeUser->name,
                                $actualFakeUser['name']);
            $this->assertEquals($expectedFakeUser->first_name,
                                $actualFakeUser['given_name']);
            $this->assertEquals($expectedFakeUser->last_name,
                                $actualFakeUser['family_name']);
            $this->assertEquals($expectedFakeUser->email,
                                $actualFakeUser['email']);
            $this->assertEquals($expectedRoles,
                                $actualFakeUser['roles']);
        }
    }

    /**
     * Test that a second NRPS with changed user information will also update
     * our real user info.
     */
    public function testExistingUsersAreUpdated()
    {
        // set up our fake NRPS response so that our second NRPS call changes
        // users email and name
        $secondFakeNrps = $this->fakeNrps;
        $secondFakeNrps['members'][0]['name'] = "instructorOne";
        $secondFakeNrps['members'][0]['email'] = "instructorOne@example.com";
        $secondFakeNrps['members'][1]['name'] = "studentOne";
        $fakeNrpsResponse = Http::sequence()
            ->push($this->fakeNrps, Response::HTTP_OK)
            ->push($secondFakeNrps);
        $this->setupHttpFake($fakeNrpsResponse);
        // do the first nrps call
        $resp = $this->withHeaders($this->headers)
                     ->get($this->nrps->getShimUrl());
        $resp->assertStatus(Response::HTTP_OK);
        // do the second nrps call
        $resp = $this->withHeaders($this->headers)
                     ->get($this->nrps->getShimUrl());
        //$resp->dump();
        $resp->assertStatus(Response::HTTP_OK);
        // make sure that the users we got back have been entered into database
        $this->assertNotEmpty($secondFakeNrps['members']); // sanity check, make
                                                // sure we aren't skipping loop
        foreach ($secondFakeNrps['members'] as $expectedRealUser) {
            $actualRealUser = LtiRealUser::firstWhere('sub',
                                                  $expectedRealUser['user_id']);
            $this->assertNotEmpty($actualRealUser);
            $this->assertEquals($expectedRealUser['name'],
                                $actualRealUser->name);
            $this->assertEquals($expectedRealUser['email'],
                                $actualRealUser->email);
            $this->assertEquals($this->platform->id,
                                $actualRealUser->platform_id);
            // make sure the real users also has a fake user created
            $fakeUser = $actualRealUser->lti_fake_users()->first();
            $this->assertNotEmpty($fakeUser);
            $this->assertEquals($this->tool->id, $fakeUser->tool_id);
        }
    }
}
