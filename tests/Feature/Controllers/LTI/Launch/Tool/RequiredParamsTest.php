<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Symfony\Component\HttpFoundation\Response;

use Tests\TestCase;

class ToolLaunchControllerTest extends TestCase
{
    /**
     * If required LTI params are missing from the login, throw a 400 error.
     *
     * @return void
     */
    public function testLoginMissingRequiredParams()
    {
		$baseUrl = '/lti/launch/tool/login';
        $response = $this->get($baseUrl);
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response = $this->get("$baseUrl?iss=1");
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response = $this->get("$baseUrl?login_hint=1");
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response = $this->get("$baseUrl?target_link_uri=1");
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response = $this->get("$baseUrl?iss=1&login_hint=1");
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response = $this->get("$baseUrl?iss=1&target_link_uri=1");
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
		$response = $this->get("$baseUrl?target_link_uri=1&login_hint=1");
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
		$response = $this->get("$baseUrl?iss=1&login_hint=1&target_link_uri=1");
        $response->assertStatus(Response::HTTP_OK);
		// both POST and GET requests needs to be supported
        $response = $this->post($baseUrl);
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response = $this->post($baseUrl, ['iss'=>1]);
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response = $this->post($baseUrl, ['login_hint'=>1]);
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response = $this->post($baseUrl, ['target_link_uri'=>1]);
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response = $this->post($baseUrl, ['iss'=>1, 'login_hint'=>1]);
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response = $this->post($baseUrl, ['iss'=>1, 'target_link_uri'=>1]);
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
		$response = $this->post($baseUrl,
			['target_link_uri'=>1, 'login_hint'=>1]);
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
		$response = $this->post($baseUrl,
			['iss'=>1, 'target_link_uri'=>1, 'login_hint'=>1]);
        $response->assertStatus(Response::HTTP_OK);
    }
}
