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

use UBC\LTI\Specs\Nrps\PlatformNrps;

// only tests the incoming requests for the platform, this is just the auth req
class NrpsTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    private const EXPECTED_ACCESS_TOKEN = 'NrpsTestExpectedAccessToken';

    private string $baseUrl = '/lti/platform/nrps/';
    private Tool $tool;
    private Platform $platform;
    private Deployment $deployment;
    private Nrps $nrps;

    private array $fakeNrps; // holds the fake NRPS response that the platform
                            // sends back, that needs to be filtered

    protected function setUp(): void
    {
        parent::setUp();
        // setup the database
        $this->seed();
        $this->tool = Tool::find(2); // php test tool
        $this->platform = Platform::find(3); // canvas test
        $this->deployment = factory(Deployment::class)->create([
            'platform_id' => $this->platform->id
        ]);
        $this->nrps = factory(Nrps::class)->create([
            'deployment_id' => $this->deployment->id,
            'tool_id' => $this->tool->id
        ]);
        // configure fake http responses
        $this->fakeNrps = [
            "id" => "http://192.168.55.182:8900/api/lti/courses/1/names_and_roles",
            "context" => [
                "id" => "4dde05e8ca1973bcca9bffc13e1548820eee93a3",
                "label" => "TEST100",
                "title" => "TEST100",
            ],
            "members" => [
                [
                    "status" => "Active",
                    "name" => "admin@example.com",
                    "picture" => "https://192.168.55.182:8900/images/messages/avatar-50.png",
                    "given_name" => "admin@example.com",
                    "family_name" => "",
                    "email" => "admin@example.com",
                    "user_id" => "cb3f9fd9-59e7-49ca-9355-f6ceda272f8d",
                    "lti11_legacy_user_id" => "535fa085f22b4655f48cd5a36a9215f64c062838",
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
        Http::fake([
            $this->nrps->context_memberships_url =>
                Http::response($this->fakeNrps),
            $this->platform->oauth_token_url => Http::response([
                'access_token' => self::EXPECTED_ACCESS_TOKEN
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
        $resp = $this->get($this->baseUrl . '9999');
        $resp->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test that the users returned by the NRPS request are entered into the
     * database and fake users created for them.
     */
    public function testContextAndMemberFiltering()
    {
        // do the nrps call
        $resp = $this->get($this->nrps->getShimUrl());
        // request should be successful
        $resp->assertStatus(Response::HTTP_OK);
        // the response must have these fields
        $resp->assertJsonStructure([
            'id',
            'context',
            'members'
        ]);
        // make sure the the course has been filtered
        $courseContext = CourseContext::firstWhere(
            'real_context_id',
            $this->fakeNrps['context']['id']
        );
        $expectedContext = ['id' => $courseContext->fake_context_id];
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
            $this->assertEquals($expectedFakeUser->email,
                                $actualFakeUser['email']);
            $this->assertEquals($expectedRoles,
                                $actualFakeUser['roles']);
        }
    }

    /**
     * Test that pagination and role filter queries are passed through as is.
     */
    public function testLimitAndRoleParamPassthrough()
    {
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

        $resp = $this->get($expectedUrl);
        $resp->assertStatus(Response::HTTP_OK);
        // make sure that the NRPS url is rewritten with the params
        $actualUrl = $resp['id'];
        $this->assertEquals($expectedUrl, $actualUrl);
    }
}
