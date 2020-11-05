<?php

namespace Tests\Feature\LTI\Ags;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

use Tests\TestCase;

use App\Models\CourseContext;
use App\Models\Deployment;
use App\Models\LtiRealUser;
use App\Models\Ags;
use App\Models\AgsLineitem;
use App\Models\Platform;
use App\Models\Tool;

use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Specs\Ags\PlatformAgs;
use UBC\LTI\Specs\Security\AccessToken;

// tests AGS calls
class AgsTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    private const SCOPE_LINEITEM =
        'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem';

    private string $baseUrl = '/lti/ags/platform/';
    private string $accessToken;
    private CourseContext $courseContext;
    private Deployment $deployment;
    private Ags $ags;
    private Platform $platform;
    private Tool $tool;

    private array $fakeAgs; // holds the fake AGS response that the platform
                            // sends back, that needs to be filtered
    private array $headers; // headers sent on each AGS request

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
        $this->ags = Ags::factory()->create([
            'course_context_id' => $this->courseContext->id,
            'deployment_id' => $this->deployment->id,
            'tool_id' => $this->tool->id
        ]);
        $ltiLog = new LtiLog('AgsTest');
        $tokenHelper = new AccessToken($ltiLog);
        $this->accessToken = $tokenHelper->create(
            $this->tool,
            [self::SCOPE_LINEITEM]
        );
        $this->headers = [
            'Accept' => 'application/vnd.ims.lis.v2.lineitemcontainer+json',
            'Authorization' => 'Bearer ' . $this->accessToken
        ];
        // configure fake http responses
        $this->fakeAgs = [
            [
                "id" => "https://lms.example.com/context/2923/lineitems/1",
                "scoreMaximum" => 60,
                "label" => "Chapter 5 Test",
                "resourceId" => "a-9334df-33",
                "tag" => "grade",
                "resourceLinkId" => "1g3k4dlk49fk",
                "endDateTime" => "2018-04-06T22:05:03Z"
            ],
            [
                "id" => "https://lms.example.com/context/2923/lineitems/47",
                "scoreMaximum" => 100,
                "label" => "Chapter 5 Progress",
                "resourceId" => "a-9334df-33",
                "tag" => "originality",
                "resourceLinkId" => "1g3k4dlk49fk"
            ],
            [
                "id" => "https://lms.example.com/context/2923/lineitems/69",
                "scoreMaximum" => 60,
                "label" => "Chapter 2 Essay",
                "tag" => "grade"
            ]
        ];
        Http::fake([
            $this->ags->lineitems => Http::response($this->fakeAgs),
            $this->fakeAgs[0]['id'] => Http::response($this->fakeAgs[0]),
            $this->platform->access_token_url => Http::response([
                'access_token' => $this->accessToken,
                'expires_in' => 3600
            ])
        ]);
    }

    /**
     * If we try to access an AGS endpoint on the shim that doesn't exist in
     * the database, we should get a 404
     *
     * @return void
     */
    public function testNonExistentAgsEndpoint()
    {
        $resp = $this->withHeaders($this->headers)->get($this->baseUrl . '9999');
        $resp->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test that the users returned by the AGS request are entered into the
     * database and each lineitem has an associated entry in the database
     */
    public function testGetLineitems()
    {
        // do the ags call
        $resp = $this->withHeaders($this->headers)
                     ->get($this->ags->getShimLineitemsUrl());
        //$resp->dump();
        // request should be successful
        $resp->assertStatus(Response::HTTP_OK);
        // make sure that each lineitem id's url was rewritten to shim's url and
        // that corresponding lineitem entries were made in the database
        $expectedLineitems = AgsLineitem::all()->take(count($this->fakeAgs));
        $this->assertEquals(count($expectedLineitems), count($this->fakeAgs));
        $expectedJson = $this->fakeAgs;
        foreach ($expectedJson as $key => &$expectedEntry) {
            $expectedEntry['id'] = $expectedLineitems[$key]->getShimLineitemUrl();
        }

        $resp->assertJson($expectedJson);
    }

    /**
     * Test that we can get info on a single lineitem.
     */
    public function testGetLineitem()
    {
        // first grab the list of lineitems
        $resp = $this->withHeaders($this->headers)
                     ->get($this->ags->getShimLineitemsUrl());
        // request should be successful
        $resp->assertStatus(Response::HTTP_OK);
        // then call the first lineitem in the returned list
        $lineitemUrl = $resp->json()[0]['id'];
        $resp = $this->withHeaders($this->headers)
                     ->get($lineitemUrl);
        //$resp->dump();
        // request should be successful
        $resp->assertStatus(Response::HTTP_OK);
        // make sure we get the right data back
        $expectedJson = $this->fakeAgs[0];
        $expectedJson['id'] = $lineitemUrl;

        $resp->assertJson($expectedJson);
        // test lineitem is protected by access token
        $headers = $this->headers;
        $headers['Authorization'] = 'Bearer ClearlyBadAccessToken';
        $resp = $this->withHeaders($headers)
                     ->get($lineitemUrl);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Make sure that AGS calls will reject invalid access tokens.
     */
    public function testRejectInvalidAccessToken()
    {
        // change the access token to a bad one
        $headers = $this->headers;
        $headers['Authorization'] = 'Bearer ClearlyBadAccessToken';

        // do the ags call
        $resp = $this->withHeaders($headers)->get($this->ags->getShimLineitemsUrl());
        // request should fail
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testMissingAccessToken()
    {
        // delete the access token
        $headers = $this->headers;
        unset($headers['Authorization']);
        // do the ags call
        $resp = $this->withHeaders($headers)->get($this->ags->getShimLineitemsUrl());
        // request should fail
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testAuthorizationHeaderCaseSensitivity()
    {
        $headers = $this->headers;
        // lower case b in bearer
        $headers['Authorization'] = 'bearer ' . $this->accessToken;
        $resp = $this->withHeaders($headers)->get($this->ags->getShimLineitemsUrl());
        $resp->assertStatus(Response::HTTP_OK);
        // lower case authorization
        unset($headers['Authorization']);
        $headers['authorization'] = 'Bearer ' . $this->accessToken;
        $resp = $this->withHeaders($headers)->get($this->ags->getShimLineitemsUrl());
        $resp->assertStatus(Response::HTTP_OK);
    }
}
