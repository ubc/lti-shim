<?php
namespace Tests\Feature\LTI\Launch\Tool;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;

use Jose\Component\Core\JWK;
use Jose\Easy\Build;

use Tests\TestCase;

use App\Models\Deployment;
use App\Models\EncryptionKey;
use App\Models\Platform;
use App\Models\Tool;

// only checks incoming requests, for tool, this is the login and the auth resp
class AuthRespTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    /**
     * If the required params are missing from the authentication resp stage,
     * throw a 400 error.
     *
     * @return void
     */
    public function testProcessAuth()
    {
        $baseUrl = '/lti/launch/tool/auth';
        // known good request
        $myTool = factory(Tool::class)->create(['id' => 1]);
        $targetTool = factory(Tool::class)->create(['id' => 2]);
        $targetPlatform = factory(Platform::class)->create();
        $encryptionKey = factory(EncryptionKey::class)->create();
        $deployment = factory(Deployment::class)->create([
            'platform_id' => $targetPlatform->id
        ]);
        $time = time();
        $loginHint = 'someLoginHint';
        $idToken = Build::jws()
            ->alg('RS256')
            ->iat($time)
            ->exp($time + 3600)
            ->iss($targetPlatform->iss)
            ->aud($targetPlatform->shim_client_id)
            ->sub($loginHint)
            ->claim('https://purl.imsglobal.org/spec/lti/claim/message_type',
                    'LtiResourceLinkRequest')
            ->claim('https://purl.imsglobal.org/spec/lti/claim/version',
                    '1.3.0')
            ->claim('https://purl.imsglobal.org/spec/lti/claim/deployment_id',
                    $deployment->lti_deployment_id)
            ->claim('https://purl.imsglobal.org/spec/lti/claim/custom',
                    ['target_tool_id' => $targetTool->id])
            ->sign($targetPlatform->getKey()->key);
        $state = Build::jwe()
            ->alg('RSA-OAEP-256') // key encryption algo
            ->enc('A256GCM') // content encryption algo
            ->nbf($time)
            ->iat($time)
            ->exp($time + 3600)
            ->claim('original_iss', $targetPlatform->iss)
            ->claim('client_id', $targetPlatform->shim_client_id)
            ->claim('login_hint', $loginHint)
            ->encrypt($encryptionKey->public_key);
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
