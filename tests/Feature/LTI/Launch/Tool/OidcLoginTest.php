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

    private string $baseUrl = '/lti/launch/tool/login';
    private Platform $platform;
    private Tool $tool;
    private array $goodParams;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->platform = Platform::find(2);
        $this->tool = Tool::find(2);

        $this->goodParams = [
            'iss' => $this->platform->iss,
            'login_hint' => 1,
            'target_link_uri' => $this->tool->shim_target_link_uri
        ];

        $encryptionKey = factory(EncryptionKey::class)->create();
    }

    public function testRejectEmptyParams()
    {
        $resp = $this->get($this->baseUrl);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
        $resp = $this->post($this->baseUrl);
        $resp->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testAcceptGet()
    {
        $resp = $this->call('get', $this->baseUrl, $this->goodParams);
        $resp->assertStatus(Response::HTTP_OK);
    }

    public function testAcceptPost()
    {
        $resp = $this->post($this->baseUrl, $this->goodParams);
        $resp->assertStatus(Response::HTTP_OK);
    }

    /**
     * If required LTI params are missing from the login, throw a 400 error.
     *
     * @return void
     */
    public function testRejectIfMissingParam()
    {
        // test one missing params
        foreach ($this->goodParams as $key => $val) {
            $badParams = $this->goodParams;
            unset($badParams[$key]);
            $resp = $this->call('get', $this->baseUrl, $badParams);
            $resp->assertStatus(Response::HTTP_BAD_REQUEST);
            $resp = $this->post($this->baseUrl, $badParams);
            $resp->assertStatus(Response::HTTP_BAD_REQUEST);
        }
    }
}

