<?php
namespace Tests\Feature\LTI\Launch\Tool;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;

use Tests\TestCase;

use App\Models\EncryptionKey;
use App\Models\Platform;
use App\Models\Tool;

// only checks incoming requests, for tool, this is the login and the auth resp
class OidcLoginTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    /**
     * If required LTI params are missing from the login, throw a 400 error.
     *
     * @return void
     */
    public function testCheckLogin()
    {
        $baseUrl = '/lti/launch/tool/login';
        $resp = $this->get($baseUrl);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
        // unrecongized platforms throw an exception, so we need to add one to
        // the database
        $platform = factory(Platform::class)->create();
        $myTool = factory(Tool::class)->create(['id' => 1]);
        $encryptionKey = factory(EncryptionKey::class)->create();
        $goodParams = [
            'iss' => $platform->iss,
            'login_hint' => 1,
            'target_link_uri' => config('app.url') . '/blah'
        ];
        // TODO: test code path with client_id
        // test both POST and GET requests
        $resp = $this->call('get', $baseUrl, $goodParams);
        $resp->assertStatus(Response::HTTP_OK);
        $resp = $this->post($baseUrl, $goodParams);
        $resp->assertStatus(Response::HTTP_OK);
        //$resp->dump(); // dump stacktrace, for debugging
        // test missing params
        $resp = $this->get($baseUrl);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
        $resp = $this->post($baseUrl);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
        // test one missing params
        foreach ($goodParams as $key => $val) {
            $badParams = $goodParams;
            unset($badParams[$key]);
            $resp = $this->call('get', $baseUrl, $badParams);
            $resp->assertStatus(Response::HTTP_BAD_REQUEST);
            $resp = $this->post($baseUrl, $badParams);
            $resp->assertStatus(Response::HTTP_BAD_REQUEST);
        }
    }
}

