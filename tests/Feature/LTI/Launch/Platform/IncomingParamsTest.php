<?php

namespace Tests\Feature\LTI\Launch\Platform;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Symfony\Component\HttpFoundation\Response;

use App\Models\Deployment;
use App\Models\Platform;
use App\Models\Tool;

use Tests\TestCase;

// only tests the incoming requests for the platform, this is just the auth req
class IncomingParamsTest extends TestCase
{
    /**
     * If required LTI params are missing from the login, throw a 400 error.
     *
     * @return void
     */
    public function testAuthReqRequiredParams()
    {
		$baseUrl = '/lti/launch/platform/auth';
        // known good request
        $tool = factory(Tool::class)->create();
        $myPlatform = factory(Platform::class)->create(['id' => 1]);
        $deployment = factory(Deployment::class)->create([
            'tool_id' => $tool->id,
            'platform_id' => $myPlatform->id
        ]);

        $loginHint = 'someUser';
        $session = [
            'client_id' => $tool->client_id,
            'login_hint' => $loginHint,
            'toolId' => $tool->id,
            'deploymentId' => $deployment->id
        ];
        // check the static values first
        $goodValues = [
            'scope' => 'openid',
            'response_type' => 'id_token',
            'response_mode' => 'form_post',
            'login_hint' => $loginHint,
            'client_id' => $tool->client_id,
            'prompt' => 'none'
        ];
        $response = $this->withSession($session)
                         ->call('get', $baseUrl, $goodValues);
        $response->assertStatus(Response::HTTP_OK);
        // no params
        $response = $this->withSession($session)->get($baseUrl);
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        // missing values
        foreach ($goodValues as $key => $val) {
            // param has a bad value
            $badValues = $goodValues;
            $badValues[$key] = $val . 'bad';
            $response = $this->withSession($session)
                             ->call('get', $baseUrl, $badValues);
            $response->assertStatus(Response::HTTP_BAD_REQUEST);
            // param is completely missing
            $badValues = $goodValues;
            unset($badValues[$key]);
            $response = $this->withSession($session)
                             ->call('get', $baseUrl, $badValues);
            $response->assertStatus(Response::HTTP_BAD_REQUEST);
        }
    }
}
