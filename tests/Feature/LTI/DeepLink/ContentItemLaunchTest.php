<?php

namespace Tests\Feature\LTI\DeepLink;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

use Symfony\Component\HttpFoundation\Response;

use Jose\Easy\Build;
use Jose\Easy\JWT;
use Jose\Easy\Load;

use Tests\Feature\LTI\LtiBasicTestCase;

use Database\Seeders\BasicTestDatabaseSeeder;

use App\Models\DeepLink;
use App\Models\DeepLinkContentItem;

use UBC\LTI\Specs\Security\Nonce;

// tests a deep link content item launch (shim launch url has a content item id)
class ContentItemLaunchTest extends LtiBasicTestCase
{
    private DeepLinkContentItem $contentItem;
    
    protected function setUp(): void
    {
        parent::setUp();

        // an existing deep link content item entry should already be present
        $this->contentItem = new DeepLinkContentItem();
        $this->contentItem->url = fake()->url();
        $this->contentItem->deployment_id = $this->deployment->id;
        $this->contentItem->tool_id = $this->tool->id;
        $this->contentItem->save();
    }

    /**
     * Test a minimal launch. The only thing different from a regular launch is
     * that when we OIDC login into the target tool, it should be using the
     * launch url stored in DeepLinkContentItem.
     */
    public function testMinimalLaunch()
    {
        // TODO test that custom params are carried over if provided by platform
        $params = [
            'iss' => $this->platform->iss,
            'login_hint' => 'StoreMeInLtiSession',
            'target_link_uri' => $this->tool->shim_target_link_uri
        ];
        // launch to the shim url
        $resp = $this->post($this->contentItem->shim_launch_url, $params);
        $resp->assertStatus(Response::HTTP_OK);
        // we should be launching to the content item's launch url
        $resp->assertViewHas('formUrl', $this->contentItem->url);
        // everything else is the same as a regular OIDC login
        $resp->assertViewHas('params.iss', config('lti.iss'));
        $resp->assertViewHas('params.target_link_uri',
                             $this->tool->target_link_uri);
        $resp->assertViewHas('params.client_id',
                             $this->tool->client_id);
    }
}
