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
            'tool_id' => $this->tool->id,
            'scopes' => [self::SCOPE_LINEITEM]
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
     * Test that filter parameters as defined by the spec are passed on properly
     * to the actual call to the platform
     */
    public function testLineitemsFilterParamPassthrough()
    {
        // make sure that spec defined filters work and that non-spec filters
        // are dropped.
        // note that the order of the query matters due to us rewriting the
        // order in ToolLineitem
        $expectedQueries = '?resource_link_id=ResourceLinkId1' .
                           '&resource_id=ResourceId1&tag=Tag1&limit=1';
        $expectedRawLineitems = [[
            "id" => "https://lms.example.com/context/111/lineitems/1",
            "scoreMaximum" => 60,
            "label" => "Lineitems Filter Passthrough",
            "resourceId" => "11111111111",
            "tag" => "filter",
            "resourceLinkId" => "111111111111",
            "endDateTime" => "2020-11-11T11:11:11Z"
        ]];
        Http::fake([
            $this->ags->lineitems . $expectedQueries => $expectedRawLineitems,
            '*' => Http::response('Failed Filter', Response::HTTP_FORBIDDEN)
        ]);
        $resp = $this->withHeaders($this->headers)
                     ->get($this->ags->getShimLineitemsUrl() .
                           $expectedQueries . '&dropme=1');
        //$resp->dump();
        // request should match what we faked for the platform url
        $resp->assertStatus(Response::HTTP_OK);
        $lineitem = AgsLineitem::find(1);
        $expectedLineitems = $expectedRawLineitems;
        $expectedLineitems[0]['id'] = $lineitem->getShimLineitemUrl();
        $resp->assertJson($expectedLineitems);
    }

    /**
     * Pagination is done using the "link" header, we need to make sure those
     * urls are rewritten to shim AGS urls.
     */
    public function testLineitemsPaginationHeaderFiltering()
    {
        $expectedQueries = '?limit=1';
        $expectedRawLineitems = [[
            "id" => "https://lms.example.com/context/111/lineitems/1",
            "scoreMaximum" => 60,
            "label" => "Lineitems Filter Passthrough",
            "resourceId" => "11111111111",
            "tag" => "filter",
            "resourceLinkId" => "111111111111",
            "endDateTime" => "2020-11-11T11:11:11Z"
        ]];
        // fake a response that contains link headers to filter
        Http::fake([
            $this->ags->lineitems . $expectedQueries => Http::response(
                $expectedRawLineitems,
                Response::HTTP_OK,
                [
                    'link' => '<http://192.168.55.182:8900/api/lti/courses/1/lineitems?page=1&per_page=1>; rel="current",<http://192.168.55.182:8900/api/lti/courses/1/lineitems?page=2&per_page=1>; rel="next"'
                ]
            ),
            '*' => Http::response('Failed Filter', Response::HTTP_FORBIDDEN)
        ]);
        $resp = $this->withHeaders($this->headers)
                     ->get($this->ags->getShimLineitemsUrl() .
                           $expectedQueries);
        //$resp->dump();
        $resp->assertStatus(Response::HTTP_OK);
        // make sure that the link header was properly replaced and that
        // the correct Ags entries are in the database
        $resp->assertHeader('link',
            '<http://localhost/lti/ags/platform/2>; rel="current",<http://localhost/lti/ags/platform/3>; rel="next"');
        $ags = Ags::find(2);
        $this->assertEquals($ags->lineitems,
            'http://192.168.55.182:8900/api/lti/courses/1/lineitems?page=1&per_page=1');
        $this->assertEquals($ags->getShimLineitemsUrl(),
            'http://localhost/lti/ags/platform/2');
        $ags = Ags::find(3);
        $this->assertEquals($ags->lineitems,
            'http://192.168.55.182:8900/api/lti/courses/1/lineitems?page=2&per_page=1');
        $this->assertEquals($ags->getShimLineitemsUrl(),
            'http://localhost/lti/ags/platform/3');
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
     * Test that a POST request to the lineitems url creates a new lineitem
     */
    public function testCreateNewLineitem()
    {
        $expectedLineitem = [
            "scoreMaximum" => 60,
            "label" => "Chapter 5 Test",
            "resourceId" => "quiz-231",
            "tag" => "grade",
            "startDateTime" => "2020-03-06T20:05:02Z",
            "endDateTime" => "2022-04-06T22:05:03Z"
        ];
        // need a new AGS endpoint to fake a response on
        $ags = Ags::factory()->create([
            'course_context_id' => $this->courseContext->id,
            'deployment_id' => $this->deployment->id,
            'tool_id' => $this->tool->id,
            'scopes' => [self::SCOPE_LINEITEM]
        ]);
        Http::fake([
            $ags->lineitems => Http::response(array_merge(
                ['id' => 'https://lms.example.com/context/2923/lineitems/81'],
                $expectedLineitem
            ))
        ]);
        // send the post request
        $resp = $this->withHeaders($this->headers)
                     ->post(
                         $ags->getShimLineitemsUrl(),
                         $expectedLineitem
                     );
        //$resp->dump();
        // make sure the post request is successful
        $resp->assertStatus(Response::HTTP_CREATED);
        // the returned lineitem should have a lineitem url added, and since
        // there's no existing lineitem in the db, it should be first one
        $expectedLineitem['id'] = $ags->getShimLineitemsUrl() . '/lineitem/1';
        $resp->assertJson($expectedLineitem);
    }

    /**
     * Test that a PUT request to a lineitem url edits that lineitem
     */
    public function testEditLineitem()
    {
        // do the ags call to get the lineitem entries
        $resp = $this->withHeaders($this->headers)
                     ->get($this->ags->getShimLineitemsUrl());
        // request should be successful
        $resp->assertStatus(Response::HTTP_OK);
        // try to edit the second lineitem
        $expectedLineitem = $resp->json()[1];
        $expectedLineitem['scoreMaximum'] += 10;
        $expectedLineitem['label'] .= ' added to label';
        // fake a successful platform response to the shim
        Http::fake([
            $this->fakeAgs[1]['id'] => array_merge(
                $expectedLineitem,
                ['id' => $this->fakeAgs[1]['id']]
            )
        ]);
        $lineitemUrl = $expectedLineitem['id'];
        // send the PUT request
        $resp = $this->withHeaders($this->headers)
                     ->put($lineitemUrl, $expectedLineitem);
        //$resp->dump();
        $resp->assertStatus(Response::HTTP_OK);
        $resp->assertJson($expectedLineitem);
    }

    /**
     * Test that a DELETE request to a lineitem url deletes that lineitem
     */
    public function testDeleteLineitem()
    {
        // do the ags call to get the lineitem entries
        $resp = $this->withHeaders($this->headers)
                     ->get($this->ags->getShimLineitemsUrl());
        // request should be successful
        $resp->assertStatus(Response::HTTP_OK);
        // try to delete the first lineitem
        $lineitemUrl = $resp->json()[0]['id'];
        // make sure the lineitem entry is there before deletion
        $this->assertNotEmpty(AgsLineitem::find(1));
        // send the DELETE request
        $resp = $this->withHeaders($this->headers)
                     ->delete($lineitemUrl);
        $resp->assertStatus(Response::HTTP_NO_CONTENT);
        // lineitem entry should be gone after deletion
        $this->assertEmpty(AgsLineitem::find(1));
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
