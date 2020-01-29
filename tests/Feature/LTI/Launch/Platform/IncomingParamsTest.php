<?php

namespace Tests\Feature\LTI\Launch\Platform;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Symfony\Component\HttpFoundation\Response;

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
        $response = $this->get($baseUrl);
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        // check the static values first
        $goodValues = [
            'scope' => 'openid',
            'response_type' => 'id_token',
            'response_mode' => 'form_post',
            'prompt' => 'none',
        ];
        $response = $this->call('get', $baseUrl, $goodValues);
        $response->assertStatus(Response::HTTP_OK);
        foreach ($goodValues as $key => $val) {
            // param has a bad value
            $badValues = $goodValues;
            $badValues[$key] = $val . 'bad';
            $response = $this->call('get', $baseUrl, $badValues);
            $response->assertStatus(Response::HTTP_BAD_REQUEST);
            // param is completely missing
            $badValues = $goodValues;
            unset($badValues[$key]);
            $response = $this->call('get', $baseUrl, $badValues);
            $response->assertStatus(Response::HTTP_BAD_REQUEST);
        }
    }
}
