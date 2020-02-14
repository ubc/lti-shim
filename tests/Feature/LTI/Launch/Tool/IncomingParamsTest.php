<?php
namespace Tests\Feature\LTI\Launch\Tool;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Symfony\Component\HttpFoundation\Response;

use Jose\Component\Core\JWK;
use Jose\Easy\Build;

use Tests\TestCase;

use App\Models\Platform;
use App\Models\Tool;

// only checks incoming requests, for tool, this is the login and the auth resp
class IncomingParamsTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    /**
     * If required LTI params are missing from the login, throw a 400 error.
     *
     * @return void
     */
    public function testLoginMissingRequiredParams()
    {
        $baseUrl = '/lti/launch/tool/login';
        $resp = $this->get($baseUrl);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
        // unrecongized platforms throw an exception, so we need to add one to
        // the database
        $platform = factory(Platform::class)->create();
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

    /**
     * If the required params are missing from the authentication resp stage,
     * throw a 400 error.
     *
     * @return void
     */
    public function testAuthMissingRequiredParams()
    {
        $baseUrl = '/lti/launch/tool/auth';
        // known good request
        $platform = factory(Platform::class)->create();
        $client = $platform->clients()->first();
        $time = time();
        $idToken = Build::jws()
            ->alg('RS256')
            ->iat($time)
            ->exp($time + 3600)
            ->iss($platform->iss)
            ->aud($client->client_id)
            ->claim('https://purl.imsglobal.org/spec/lti/claim/message_type',
                    'LtiResourceLinkRequest')
            ->claim('https://purl.imsglobal.org/spec/lti/claim/version',
                    '1.3.0')
            ->sign($platform->keys()->first()->public_key);
        $state = Build::jws()
            ->alg('RS256')
            ->iat($time)
            ->exp($time + 3600)
            ->iss(config('lti.iss'))
            ->claim('original_iss', $platform->iss)
            ->claim('client_id', $client->client_id)
            ->claim('login_hint', 'blah')
            ->sign(JWK::createFromJson(config('lti.key')));
        $resp = $this->post($baseUrl, ['state'=>$state, 'id_token'=>$idToken]);
        // success should give us a 302 redirect
        $resp->assertStatus(Response::HTTP_FOUND);

        // can't use get requests for the authentication resp
        $resp = $this->get($baseUrl);
        $resp->assertStatus(Response::HTTP_METHOD_NOT_ALLOWED);
        // requests that are missing a required param
        $resp = $this->post($baseUrl);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
        $resp = $this->post($baseUrl, ['state' => $state]);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
        $resp = $this->post($baseUrl, ['id_token' => $idToken]);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);

    }
}