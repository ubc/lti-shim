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
class AgsResultTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    private const SCOPE_RESULT =
        'https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly';

    private string $accessToken;
    private CourseContext $courseContext;
    private Deployment $deployment;
    private Ags $ags;
    private AgsLineitem $lineitem;
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
            'scopes' => [self::SCOPE_RESULT]
        ]);
        $this->lineitem = AgsLineitem::factory()->create([
            'ags_id' => $this->ags->id
        ]);
        $ltiLog = new LtiLog('AgsTest');
        $tokenHelper = new AccessToken($ltiLog);
        $this->accessToken = $tokenHelper->create(
            $this->tool,
            [self::SCOPE_RESULT]
        );
        $this->headers = [
            'Accept' => 'application/vnd.ims.lis.v2.lineitemcontainer+json',
            'Authorization' => 'Bearer ' . $this->accessToken
        ];
        // configure fake http responses
        Http::fake([
            $this->platform->access_token_url => Http::response([
                'access_token' => $this->accessToken,
                'expires_in' => 3600
            ])
        ]);
    }

    /**
     * Test that a GET request to a result url returns the grades for that
     * lineitem.
     */
    public function testGetResults()
    {
        // fake a results response from the platform
        $expectedResults = [
            [
                "id" => "https://lms.example.com/context/2923/lineitems/1/results/5323497",
                "scoreOf" => "https://lms.example.com/context/2923/lineitems/1",
                "userId" => "1111111",
                "resultScore" => 0.83,
                "resultMaximum" => 1,
                "comment" => "This is exceptional work."
            ],
            [
                "id" => "https://lms.example.com/context/2923/lineitems/1/results/5323497",
                "scoreOf" => "https://lms.example.com/context/2923/lineitems/1",
                "userId" => "2222222",
                "resultScore" => 0.47,
                "resultMaximum" => 1,
                "comment" => "This is ok work."
            ]
        ];
        Http::fake([
            $this->lineitem->lineitem_results => $expectedResults,
            '*' => Http::response('Test failed', Response::HTTP_FORBIDDEN)
        ]);

        // call the shim results endpoint
        $resp = $this->withHeaders($this->headers)
                     ->get($this->lineitem->shim_lineitem_results_url);
        //$resp->dump();
        $resp->assertStatus(Response::HTTP_OK);
        $resp->assertJson($expectedResults);
    }

    /**
     * Make sure that AGS calls are checking access tokens
     */
    /* TODO
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
     */
}
