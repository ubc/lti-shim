<?php
namespace Tests\Features\LTI\Jwks;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;

use Tests\TestCase;

use App\Models\Platform;
use App\Models\PlatformKey;
use App\Models\Tool;
use App\Models\ToolKey;

class JwksTest extends TestCase
{
    use RefreshDatabase; // reset database after each test

    public function testPlatformJwks()
    {
		$baseUrl = '/lti/platform/jwks';
        // the factory should've created at least 1 key 
        $myPlatform = factory(Platform::class)->create(['id' => 1]);
        $keys = $myPlatform->keys;
        $expectedJson = ['keys' => []];
        foreach ($keys as $key) {
            $expectedJson['keys'][] = $key->public_key->all();
        }
        $resp = $this->get($baseUrl);
        $resp->assertExactJson($expectedJson);
        // add a new key and see if it updates
        $newKey = factory(PlatformKey::class)->create([
            'platform_id' => $myPlatform->id
        ]);
        $expectedJson['keys'][] = $newKey->public_key->all();
        $resp = $this->get($baseUrl);
        $resp->assertExactJson($expectedJson);
    }

    public function testToolJwks()
    {
		$baseUrl = '/lti/tool/jwks';
        // the factory should've created at least 1 key 
        $myTool = factory(Tool::class)->create(['id' => 1]);
        $keys = $myTool->keys;
        $expectedJson = ['keys' => []];
        foreach ($keys as $key) {
            $expectedJson['keys'][] = $key->public_key->all();
        }
        $resp = $this->get($baseUrl);
        $resp->assertExactJson($expectedJson);
        // add a new key and see if it updates
        $newKey = factory(ToolKey::class)->create([
            'tool_id' => $myTool->id
        ]);
        $expectedJson['keys'][] = $newKey->public_key->all();
        $resp = $this->get($baseUrl);
        $resp->assertExactJson($expectedJson);
    }
}

