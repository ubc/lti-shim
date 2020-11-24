<?php

namespace Tests\Feature\LTI\Ags;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

use Tests\TestCase;

use App\Models\CourseContext;
use App\Models\Deployment;
use App\Models\LtiRealUser;
use App\Models\LtiFakeUser;
use App\Models\Ags;
use App\Models\AgsLineitem;
use App\Models\AgsResult;
use App\Models\Platform;
use App\Models\Tool;

use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Specs\Ags\PlatformAgs;
use UBC\LTI\Specs\Security\AccessToken;

// tests AGS calls
class AgsScoreTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    private const SCOPE_SCORE =
        'https://purl.imsglobal.org/spec/lti-ags/scope/score';

    private string $accessToken;
    private CourseContext $courseContext;
    private Deployment $deployment;
    private Ags $ags;
    private AgsLineitem $lineitem;
    private Platform $platform;
    private Tool $tool;
    private LtiRealUser $realUser1;
    private LtiFakeUser $fakeUser1;

    private array $fakeResults; // holds the fake AGS results that the platform
                                // sends back
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
            'scopes' => [self::SCOPE_SCORE]
        ]);
        $this->lineitem = AgsLineitem::factory()->create([
            'ags_id' => $this->ags->id
        ]);
        $ltiLog = new LtiLog('AgsTest');
        $tokenHelper = new AccessToken($ltiLog);
        $this->accessToken = $tokenHelper->create(
            $this->tool,
            [self::SCOPE_SCORE]
        );
        $this->headers = [
            'Accept' => 'application/vnd.ims.lis.v2.lineitemcontainer+json',
            'Authorization' => 'Bearer ' . $this->accessToken
        ];
        $this->realUser1 = LtiRealUser::factory()->create([
            'platform_id' => $this->platform->id
        ]);
        $this->fakeUser1 = LtiFakeUser::getByRealUser(
            $this->ags->course_context_id,$this->ags->tool_id,$this->realUser1);
        $this->fakeScore = [
            "timestamp" => "2017-04-16T18:54:36.736+00:00",
            "scoreGiven" => 83,
            "scoreMaximum" => 100,
            "comment" => "This is exceptional work.",
            "activityProgress" => "Completed",
            "gradingProgress"=> "FullyGraded",
            "userId" => $this->fakeUser1->sub
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
     * Test that a POST request to a score url adds a grade to that lineitem.
     */
    public function testPostScore()
    {
        $rawResultUrl = [
            'resultUrl' => 'http://example.com/lti/course/1/lineitem/1/results/1'
        ];
        Http::fake([
            $this->lineitem->lineitem_scores => $rawResultUrl,
            '*' => Http::response('Test failed', Response::HTTP_FORBIDDEN)
        ]);

        // call the shim results endpoint
        $resp = $this->withHeaders($this->headers)
                     ->post($this->lineitem->shim_lineitem_scores_url,
                            $this->fakeScore);
        //$resp->dump();
        $resp->assertStatus(Response::HTTP_OK);
        // should've created a result entry in the database
        $result = AgsResult::find(1);
        $expectedResultUrl = [
            'resultUrl' => $result->shim_url
        ];
        $resp->assertJson($expectedResultUrl);
        $this->assertEquals($rawResultUrl['resultUrl'], $result->result);
    }

    /**
     * Make sure that AGS result calls are checking access tokens
     */
    public function testRejectInvalidAccessToken()
    {
        Http::fake([
            $this->lineitem->lineitem_scores => $this->fakeScore,
            '*' => Http::response('Test failed', Response::HTTP_FORBIDDEN)
        ]);
        // change the access token to a bad one
        $headers = $this->headers;
        $headers['Authorization'] = 'Bearer ClearlyBadAccessToken';

        $expectedScore = $this->fakeScore;
        $expectedScore['userId'] = $this->fakeUser1->sub;
        // do the ags call
        $resp = $this->withHeaders($headers)
                     ->post($this->lineitem->shim_lineitem_scores_url,
                            $expectedScore);
        // request should fail
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }
}
