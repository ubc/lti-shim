<?php

namespace Tests\Feature\AdminApi;

use Illuminate\Support\Facades\Http;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;

use Laravel\Sanctum\Sanctum;

use Symfony\Component\HttpFoundation\Response;

use Tests\Feature\LTI\LtiBasicTestCase;

use App\Models\LtiSession;
use App\Models\PlatformClient;
use App\Models\User;

class PlatformClientControllerTest extends LtiBasicTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // establish an authenticated user session
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
    }

    private function getUrl(int $platformId, int $clientId = 0): string
    {
        $url = '/api/platform/' . $platformId . '/client';
        if ($clientId > 0)
            $url .= '/' . $clientId;
        return $url;
    }

    /**
     * Get a platform's list of clients (tools that are configured on the
     * platform)
     */
    public function testGetAllPlatformClients()
    {
        $url = $this->getUrl($this->platform->id);
        // call the shim results endpoint
        $resp = $this->getJson($url);
        $resp->assertStatus(Response::HTTP_OK);
        $resp->assertJsoncount(1);
        $resp->assertJson(fn (AssertableJson $json) =>
            $json->has('0', fn ($json) =>
                $json->where('id', $this->platformClient->id)
                     ->where('platform_id', $this->platformClient->platform_id)
                     ->where('tool_id', $this->platformClient->tool_id)
                     ->where('client_id', $this->platformClient->client_id)
                     ->etc()
            )
        );

        $url = $this->getUrl($this->platform2->id);
        // call the shim results endpoint
        $resp = $this->getJson($url);
        $resp->assertStatus(Response::HTTP_OK);
        $resp->assertJsoncount(1);
        $resp->assertJson(fn (AssertableJson $json) =>
            $json->has('0', fn ($json) =>
                $json->where('id', $this->platformClient2->id)
                     ->where('platform_id', $this->platformClient2->platform_id)
                     ->where('tool_id', $this->platformClient2->tool_id)
                     ->where('client_id', $this->platformClient2->client_id)
                     ->etc()
            )
        );
    }

    /**
     * Create a connection between platform1 and tool2
     */
    public function testCreateNewPlatformClient()
    {
        $expectedClientId = 'Expected Client ID';

        $this->assertEquals(2, PlatformClient::count());

        $url = $this->getUrl($this->platform->id);
        $resp = $this->postJson($url, [
            'platform_id' => $this->platform->id,
            'tool_id' => $this->tool2->id,
            'client_id' => $expectedClientId
        ]);
        $resp->assertStatus(Response::HTTP_CREATED);
        $this->assertEquals(3, PlatformClient::count());
        $resp->assertJson(fn (AssertableJson $json) =>
            $json->where('platform_id', $this->platform->id)
                 ->where('tool_id', $this->tool2->id)
                 ->where('client_id', $expectedClientId)
                 ->etc()
        );
    }

    /**
     * Create a conflict between the platform ID given in the url and in
     * the JSON.
     */
    public function testCreateNewPlatformClientMismatchedPlatformId()
    {
        // the platform ID in the url is platform1
        $url = $this->getUrl($this->platform->id);
        // but the platform ID in the payload is platform2, this should error
        // out
        $resp = $this->postJson($url, [
            'platform_id' => $this->platform2->id,
            'tool_id' => $this->tool2->id,
            'client_id' => 'Expected Client ID'
        ]);
        $resp->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Retrieve a specific platform client
     */
    public function testGetPlatformClient()
    {
        $url = $this->getUrl($this->platform->id, $this->platformClient->id);
        $resp = $this->getJson($url);
        $resp->assertStatus(Response::HTTP_OK);
        $resp->assertJson(fn (AssertableJson $json) =>
            $json->where('platform_id', $this->platformClient->platform_id)
                 ->where('tool_id', $this->platformClient->tool_id)
                 ->where('client_id', $this->platformClient->client_id)
                 ->where('id', $this->platformClient->id)
                 ->etc()
        );
    }

    /**
     * Edit a specific platform client
     */
    public function testEditPlatformClient()
    {
        $expectedClientId = 'The New Edited Client ID';
        $url = $this->getUrl($this->platform->id, $this->platformClient->id);
        $resp = $this->putJson($url, [
            'platform_id' => $this->platformClient->platform_id,
            'tool_id' => $this->platformClient->tool_id,
            'client_id' => $expectedClientId 
        ]);
        $resp->assertStatus(Response::HTTP_OK);
        $resp->assertJson(fn (AssertableJson $json) =>
            $json->where('platform_id', $this->platformClient->platform_id)
                 ->where('tool_id', $this->platformClient->tool_id)
                 ->where('client_id', $expectedClientId)
                 ->where('id', $this->platformClient->id)
                 ->etc()
        );
        $this->platformClient->refresh();
        $this->assertEquals($expectedClientId, $this->platformClient->client_id);
    }

    /**
     * Delete a platform client
     */
    public function testDeletePlatformClient()
    {
        $this->assertEquals(2, PlatformClient::count());
        $url = $this->getUrl($this->platform->id, $this->platformClient->id);
        $resp = $this->deleteJson($url);
        $resp->assertStatus(Response::HTTP_OK);
        $this->assertEquals(1, PlatformClient::count());
        $this->assertDatabaseMissing('platform_clients',
                                    ['id' => $this->platformClient->id]);
    }
}
