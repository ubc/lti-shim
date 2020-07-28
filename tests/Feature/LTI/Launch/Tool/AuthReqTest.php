<?php
namespace Tests\Feature\LTI\Launch\Tool;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

use Tests\TestCase;

use App\Models\EncryptionKey;
use App\Models\Platform;
use App\Models\Tool;

// only checks incoming requests, for tool, this is the login and the auth resp
class AuthReqTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    // make sure that the data we want to pass back to the platform is correct
    public function testGetLoginResponse()
    {
        $baseUrl = '/lti/launch/tool/login';
        $platform = factory(Platform::class)->create();
        $myTool = factory(Tool::class)->create(['id' => 1]);
        $encryptionKey = factory(EncryptionKey::class)->create();
        $loginHint = 'fakeLoginHint';
        $messageHint = 'fakeMessageHint';
        $goodParams = [
            'iss' => $platform->iss,
            'login_hint' => $loginHint,
            'target_link_uri' => config('app.url') . '/blah',
            'lti_message_hint' => $messageHint
        ];
        $resp = $this->call('get', $baseUrl, $goodParams);
        $resp->assertStatus(Response::HTTP_OK);
        $resp->assertViewHas('auth_req_url', $platform->auth_req_url);
        // can't figure out how to calculate the exact same 'state',
        // so we can't use the assertViewHasAll() function
        $resp->assertViewHas('response.scope', 'openid');
        $resp->assertViewHas('response.response_type', 'id_token');
        $resp->assertViewHas('response.client_id',
                             $platform->shim_client_id);
        $resp->assertViewHas('response.redirect_uri', $myTool->auth_resp_url);
        $resp->assertViewHas('response.login_hint', $loginHint);
        $resp->assertViewHas('response.response_mode', 'form_post');
        $resp->assertViewHas('response.prompt', 'none');
        $resp->assertViewHas('response.lti_message_hint', $messageHint);
        // test nonce is properly stored in the database
        $nonceResult = DB::table('cache_nonce')->first();
        $nonce = str_replace('lti_shim_cache', '', $nonceResult->key);
        $resp->assertViewHas('response.nonce', $nonce);
    }
}
