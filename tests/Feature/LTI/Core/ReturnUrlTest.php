<?php

namespace Tests\Feature\LTI\Core;

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
use App\Models\ReturnUrl;
use App\Models\Tool;

use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Specs\Ags\PlatformAgs;
use UBC\LTI\Specs\Security\AccessToken;

// tests the return url endpoint on the shim
class ReturnUrlTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    private const SCOPE_SCORE =
        'https://purl.imsglobal.org/spec/lti-ags/scope/score';

    private string $accessToken;
    private CourseContext $courseContext;
    private Deployment $deployment;
    private Platform $platform;
    private ReturnUrl $returnUrl;
    private Tool $tool;

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
        $this->returnUrl = ReturnUrl::factory()->create([
            'course_context_id' => $this->courseContext->id,
            'deployment_id' => $this->deployment->id,
            'tool_id' => $this->tool->id
        ]);
    }

    /**
     * Test that a GET request to a shim return url endpoint redirects us to
     * the original url.
     */
    public function testReturnUrlRedirect()
    {
        // call the shim results endpoint
        $resp = $this->get($this->returnUrl->getShimUrl());
        $resp->assertRedirect($this->returnUrl->url);
    }

    /**
     * Test that we verify the access token to the url
     */
    public function testReturnUrlRejectsInvalidToken()
    {
        // call the shim results endpoint with an invalid token
        $resp = $this->get($this->returnUrl->getShimUrl() . 'invalid');
        $resp->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test that supported message queries are forwarded on to the endpoint.
     */
    public function testReturnUrlMessageQueryForwarding()
    {
        $expectedQueries = '?lti_msg=showMeToUser&lti_errormsg=someError' .
            '&lti_log=someLog&lti_errorlog=someErrorLog';
        $resp = $this->get($this->returnUrl->getShimUrl([
            'lti_msg' => 'showMeToUser',
            'lti_errormsg' => 'someError',
            'lti_log' => 'someLog',
            'lti_errorlog' => 'someErrorLog',
            'invalid' => 'excludeMe'
        ]));
        $resp->assertRedirect($this->returnUrl->url . $expectedQueries);
    }

}
