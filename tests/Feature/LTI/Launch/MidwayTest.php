<?php

namespace Tests\Feature\LTI\Launch;

use Illuminate\Testing\TestResponse;

use Symfony\Component\HttpFoundation\Response;

use Tests\Feature\LTI\LtiBasicTestCase;

// tests that midway sends us to the right views with the right params
class MidwayTest extends LtiBasicTestCase
{
    private const CLAIM_MESSAGE_TYPE_URI = 'https://purl.imsglobal.org/spec/lti/claim/message_type';
    private const CLAIM_ROLE_URI = 'https://purl.imsglobal.org/spec/lti/claim/roles';
    private const ROLE_INSTRUCTOR_URI = 'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor';
    private const ROLE_STUDENT_URI = 'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner';

    // hardcoded as a check that the router is using the urls we expect
    private string $expectedToken = 'someFakeTokenCauseItsNotUsed';
    private string $expectedRedirectUri =
        'http://some.example.edu/lti/redirect';
    private string $midwayUrl = '/lti/launch/midway';
    private array $baseParams = []; // the base params required by midway

    protected function setUp(): void
    {
        parent::setUp();

        $this->ltiSession->token = [
            self::CLAIM_MESSAGE_TYPE_URI => 'LtiResourceLinkRequest',
            self::CLAIM_ROLE_URI => [self::ROLE_INSTRUCTOR_URI],
        ];
        $this->ltiSession->save();

        $this->baseParams = [
            'midwayRedirectUri' => $this->expectedRedirectUri,
            'midwaySession' => $this->ltiSession->createEncryptedId(),
            'id_token' => $this->expectedToken
        ];
    }

    /**
     * Midway is disabled by default, call this to enable it.
     */
    private function enableMidwayLookup()
    {
        $this->tool->enable_midway_lookup = true;
        $this->tool->save();
    }

    /**
     * Test that if midway is enabled, instructors are sent to the lookup tool.
     */
    public function testMidwayEnabledInstructorSentToLookupTool()
    {
        $this->enableMidwayLookup();

        $resp = $this->post($this->midwayUrl, $this->baseParams);
        $this->checkSuccessfulResponse($resp, $this->baseParams);
    }

    /**
     * Test that if midway is disabled, instructors skip the lookup tool and
     * get sent to the target tool.
     */
    public function testMidwayDisabledInstructorSentToTargetTool()
    {
        $resp = $this->post($this->midwayUrl, $this->baseParams);
        $this->checkSuccessfulResponse($resp, $this->baseParams);
    }

    /**
     * Test that if the optional state param is sent, we'll see it in the view.
     */
    public function testOptionalStateParamIsPassed()
    {
        $params = $this->baseParams;
        $params['state'] = 'MakeSureOptionalStateIsPresent';
        $resp = $this->post($this->midwayUrl, $params);
        $this->checkSuccessfulResponse($resp, $params);
    }

    /**
     * Test that students are sent to the target tool.
     */
    public function testStudentSentToTargetTool()
    {
        $token = $this->ltiSession->token;
        $token[self::CLAIM_ROLE_URI] = [self::ROLE_STUDENT_URI];
        $this->ltiSession->token = $token;
        $this->ltiSession->save();
        $this->enableMidwayLookup();

        $resp = $this->post($this->midwayUrl, $this->baseParams);
        $this->checkSuccessfulResponse($resp, $this->baseParams);
    }

    private function checkSuccessfulResponse(
        TestResponse $resp,
        array $params
    ) {
        $resp->assertStatus(Response::HTTP_OK);
        if (
            $this->tool->enable_midway_lookup &&
            $this->ltiSession->token[self::CLAIM_ROLE_URI][0] ==
                                                    self::ROLE_INSTRUCTOR_URI
        ) {
            // if midway is enabled and the user is an instructor, they should
            // be sent to the lookup tool
            $resp->assertViewIs('lti.launch.midway.lookup');
            $resp->assertViewHas('midwayRedirectUri',
                                 $this->expectedRedirectUri);
            $resp->assertViewHas('id_token', $this->expectedToken);
            if (isset($params['state'])) {
                $resp->assertViewHas('state', $params['state']);
            }
        }
        else {
            $resp->assertViewIs('lti.launch.auto_submit_form');
            $resp->assertViewHas('formUrl', $this->expectedRedirectUri);
            $resp->assertViewHas('params.id_token', $this->expectedToken);
            if (isset($params['state'])) {
                $resp->assertViewHas('params.state', $params['state']);
            }
        }
    }

    /**
     * Test that a midway lookup only launch works, even if midway lookup is
     * not enabled.
     */
    public function testMidwayLookupOnly()
    {
        $this->ltiSession->state = ['sessionType' => 'midwayLookupOnly'];
        $this->ltiSession->save();
        $resp = $this->post($this->midwayUrl, $this->baseParams);
        $resp->assertStatus(Response::HTTP_OK);
        $resp->assertViewIs('lti.launch.midway.lookup');
    }

    /**
     * Test that students are forbidden when doing a midway lookup only launch.
     */
    public function testStudentsNotAllowedMidwayLookupOnly()
    {
        $token = $this->ltiSession->token;
        $token[self::CLAIM_ROLE_URI] = [self::ROLE_STUDENT_URI];
        $this->ltiSession->state = ['sessionType' => 'midwayLookupOnly'];
        $this->ltiSession->token = $token;
        $this->ltiSession->save();

        $resp = $this->post($this->midwayUrl, $this->baseParams);
        $resp->assertStatus(Response::HTTP_FORBIDDEN);
    }

}
