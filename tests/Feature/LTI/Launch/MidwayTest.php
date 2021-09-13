<?php

namespace Tests\Feature\LTI\Launch;

use Illuminate\Testing\TestResponse;

use Laravel\Sanctum\PersonalAccessToken;

use Symfony\Component\HttpFoundation\Response;

use Tests\Feature\LTI\LtiBasicTestCase;

use App\Models\LtiFakeUser;
use App\Models\User;

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

        // have to create a fake user identity ourselves to simulate the test
        // having already gone through the lti launch before reaching midway.
        // Also need to disable first time setup since most of the tests are
        // expecting the user to have already passed that.
        $fakeUser = LtiFakeUser::getByRealUser(
            $this->ltiSession->course_context_id,
            $this->ltiSession->tool_id,
            $this->ltiSession->lti_real_user
        );
        $fakeUser->enable_first_time_setup = false;
        $fakeUser->save();

        $this->baseParams = [
            'midwayRedirectUri' => $this->expectedRedirectUri,
            'midwaySession' => $this->ltiSession->createEncryptedId(),
            'id_token' => $this->expectedToken
        ];
    }

    /**
     * We disabled first time setup in setUp(), so need to call this to
     * re-enable first time setup.
     */
    private function enableFirstTimeSetup()
    {
        $fakeUser = $this->ltiSession->lti_fake_user;
        $fakeUser->enable_first_time_setup = true;
        $fakeUser->save();
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
     * Set the user role to be a student
     */
    private function enableStudentUser()
    {
        $token = $this->ltiSession->token;
        $token[self::CLAIM_ROLE_URI] = [self::ROLE_STUDENT_URI];
        $this->ltiSession->token = $token;
        $this->ltiSession->save();
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
    public function testOptionalStateParamIsPassedToLookup()
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
        $this->enableStudentUser();
        $this->enableMidwayLookup();

        $resp = $this->post($this->midwayUrl, $this->baseParams);
        $this->checkSuccessfulResponse($resp, $this->baseParams);
    }

    /**
     * Test that instructor user is shown the first time setup page if their
     * fake identity needs it.
     */
    public function testInstructorSentToFirstTimeSetup()
    {
        $this->enableFirstTimeSetup();

        $resp = $this->post($this->midwayUrl, $this->baseParams);
        $this->checkSuccessfulResponse($resp, $this->baseParams);
    }

    public function testStudentSentToFirstTimeSetup()
    {
        $this->enableStudentUser();
        $this->enableFirstTimeSetup();

        $resp = $this->post($this->midwayUrl, $this->baseParams);
        $this->checkSuccessfulResponse($resp, $this->baseParams);
    }
    
    /**
     * Test that if the optional state param is sent, we'll see it in the view.
     */
    public function testOptionalStateParamIsPassedToFirstTimeSetup()
    {
        $this->enableFirstTimeSetup();

        $params = $this->baseParams;
        $params['state'] = 'MakeSureOptionalStateIsPresent';

        $resp = $this->post($this->midwayUrl, $params);
        $this->checkSuccessfulResponse($resp, $params);
    }

    private function checkSuccessfulResponse(
        TestResponse $resp,
        array $params
    ) {
        $resp->assertStatus(Response::HTTP_OK);

        $isInstructor = ($this->ltiSession->token[self::CLAIM_ROLE_URI][0] ==
                                                    self::ROLE_INSTRUCTOR_URI);
        if ($this->ltiSession->lti_fake_user->enable_first_time_setup) {
            // everyone needs to go through first time setup
            $resp->assertViewIs('lti.launch.midway.first_time_setup');
            // check the api token is valid
            $resp->assertViewHas('token');

            $apiUser = User::getMidwayApiUser();
            $apiUser->withAccessToken(
                PersonalAccessToken::findToken($resp['token']));

            $fakeUser = $this->ltiSession->lti_fake_user;
            $this->assertTrue($apiUser->tokenCan(
                    $apiUser->getSelectAnonymizationAbility($fakeUser->id)));
            // check continuation params
            $resp->assertViewHas('midwayRedirectUri',
                                 $this->expectedRedirectUri);
            $resp->assertViewHas('id_token', $this->expectedToken);
            if (isset($params['state'])) {
                $resp->assertViewHas('state', $params['state']);
            }
            // check vue params
            $resp->assertViewHas('fakeUserId',
                                 $this->ltiSession->lti_fake_user->id);
            $resp->assertViewHas('isMidwayOnly');
            $resp->assertViewHas('toolName', $this->ltiSession->tool->name);
        }
        elseif ($this->tool->enable_midway_lookup && $isInstructor)
        {
            // if midway is enabled and the user is an instructor, they should
            // be sent to the lookup tool
            $resp->assertViewIs('lti.launch.midway.lookup');
            // check continuation params
            $resp->assertViewHas('midwayRedirectUri',
                                 $this->expectedRedirectUri);
            $resp->assertViewHas('id_token', $this->expectedToken);
            if (isset($params['state'])) {
                $resp->assertViewHas('state', $params['state']);
            }
            // check api token
            $resp->assertViewHas('token');
            $apiUser = User::getMidwayApiUser();
            $apiUser->withAccessToken(
                PersonalAccessToken::findToken($resp['token']));
            $this->assertTrue($apiUser->tokenCan($apiUser->getLookupAbility(
                $this->ltiSession->course_context_id,
                $this->ltiSession->tool_id
            )));
            // check vue params
            $resp->assertViewHas('isMidwayOnly');
            $resp->assertViewHas('platformName',
                                 $this->ltiSession->deployment->platform->name);
            $resp->assertViewHas('toolName', $this->ltiSession->tool->name);
            $resp->assertViewHas('toolId', $this->ltiSession->tool_id);
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
        $this->enableStudentUser();

        $this->ltiSession->state = ['sessionType' => 'midwayLookupOnly'];
        $this->ltiSession->save();

        $resp = $this->post($this->midwayUrl, $this->baseParams);
        $resp->assertStatus(Response::HTTP_FORBIDDEN);
    }

}
