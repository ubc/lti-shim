<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

use Tests\Feature\LTI\LtiBasicTestCase;

use App\Models\LtiFakeUser;
use App\Models\User;

class MidwayApiControllerTest extends LtiBasicTestCase
{
    private User $midwayApiUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->midwayApiUser = User::getMidwayApiUser();
    }

    private function getLookupUrl(int $courseId, int $toolId): string
    {
        return '/api/midway/users/course_context/' . $courseId .  '/tool/' .
            $toolId . '?' . 'showToolUsers=false';
    }

    private function getSelectAnonymizationUrl(int $fakeUserId): string
    {
        return '/api/midway/config/anonymization/' . $fakeUserId;
    }

    private function getLookupAbility(int $courseId, int $toolId): string
    {
        return 'lookup:' . $courseId . ':' . $toolId;
    }

    private function getSelectAnonymizationAbility(int $fakeUserId): string
    {
        return 'select:anonymization:' . $fakeUserId;
    }

    private function getToken(array $abilities): string
    {
        return $this->midwayApiUser->createToken('SomeTokenHere', $abilities)
                                   ->plainTextToken;
    }

    private function getHeaders(string $token): array
    {
        return ['Authorization' => 'Bearer ' . $token];
    }

    /**
     * Test a basic successful lookup call
     */
    public function testBasicGetLtiFakeUsersCall()
    {
        $url = $this->getLookupUrl($this->courseContext->id, $this->tool->id);
        $token = $this->getToken([
            $this->getLookupAbility($this->courseContext->id, $this->tool->id)
        ]);

        $resp = $this->withHeaders($this->getHeaders($token))->getJson($url);
        $resp->assertStatus(Response::HTTP_OK);
    }

    /**
     * Test that the lookup call checks the token.
     */
    public function testRejectValidTokenWithEmptyAbilityList()
    {
        $url = $this->getLookupUrl($this->courseContext->id, $this->tool->id);
        $token = $this->getToken([]);

        $resp = $this->withHeaders($this->getHeaders($token))->getJson($url);
        $resp->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * Test that the lookup call checks token for correct course & tool.
     */
    public function testRejectValidTokenWithIncorrectAbilityList()
    {
        $url = $this->getLookupUrl($this->courseContext->id, $this->tool->id);
        // the lookup ability refers to tool2 while we're trying to access tool1
        $token = $this->getToken([
            $this->getLookupAbility($this->courseContext->id, $this->tool2->id)
        ]);

        $resp = $this->withHeaders($this->getHeaders($token))->getJson($url);
        $resp->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * Test that the user can set whether the lti user is anonymize or not.
     */
    public function testSetLtiFakeUserIsAnonymized()
    {
        // create a fake lti user for which we're configuring the anonymization
        // option
        $fakeUser = LtiFakeUser::getByRealUser($this->courseContext->id,
            $this->tool->id, $this->realUser);

        $this->assertTrue($fakeUser->is_anonymized);
        $this->assertTrue($fakeUser->enable_first_time_setup);

        $url = $this->getSelectAnonymizationUrl($fakeUser->id);
        $token = $this->getToken([
            $this->getSelectAnonymizationAbility($fakeUser->id)
        ]);

        // first try setting is_anonymized to false
        $resp = $this->withHeaders($this->getHeaders($token))->postJson($url,
            ['is_anonymized' => false]);
        $resp->assertStatus(Response::HTTP_OK);

        $fakeUser->refresh();
        $this->assertFalse($fakeUser->is_anonymized);
        $this->assertFalse($fakeUser->enable_first_time_setup);

        // then try setting is_anonymized to true
        $resp = $this->withHeaders($this->getHeaders($token))->postJson($url,
            ['is_anonymized' => true]);
        $resp->assertStatus(Response::HTTP_OK);

        $fakeUser->refresh();
        $this->assertTrue($fakeUser->is_anonymized);
        $this->assertFalse($fakeUser->enable_first_time_setup);
    }

    /**
     * Test that we can't use a token for one user to set the anonymization
     * option for another user
     */
    public function testStoreAnonymizationOptionRejectTokenWithWrongUser()
    {
        // create a fake lti user for which we're configuring the anonymization
        // option
        $fakeUser = LtiFakeUser::getByRealUser($this->courseContext->id,
            $this->tool->id, $this->realUser);

        $url = $this->getSelectAnonymizationUrl($fakeUser->id);
        $token = $this->getToken([
            $this->getSelectAnonymizationAbility($fakeUser->id + 1)
        ]);

        $resp = $this->withHeaders($this->getHeaders($token))->postJson($url,
            ['is_anonymized' => false]);
        $resp->assertStatus(Response::HTTP_FORBIDDEN);
    }
}
