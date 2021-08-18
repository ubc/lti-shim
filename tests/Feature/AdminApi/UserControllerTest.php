<?php

namespace Tests\Feature\AdminApi;

use Illuminate\Support\Facades\Http;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;

use Laravel\Sanctum\Sanctum;

use Symfony\Component\HttpFoundation\Response;

use Tests\Feature\LTI\LtiBasicTestCase;

use App\Models\User;

class UserControllerTest extends LtiBasicTestCase
{
    private User $unauthedUser;
    private User $authedUser;
    
    protected function setUp(): void
    {
        parent::setUp();

        $this->unauthedUser = User::factory()->create();
        $this->authedUser = User::factory()->create();
        // establish an authenticated user session
        Sanctum::actingAs(
            $this->authedUser,
            ['*']
        );
    }

    /**
     * Test getting the user info for the currently authenticated user
     */
    public function testGetSelf()
    {
        $resp = $this->getJson('/api/user/self');
        $resp->assertStatus(Response::HTTP_OK);
        $resp->assertJson(fn (AssertableJson $json) =>
            $json->where('id', $this->authedUser->id)
                 ->where('name', $this->authedUser->name)
                 ->where('email', $this->authedUser->email)
                 ->etc()
        );
    }

    /**
     * Test getting a user by id. In case the get self route overwrote this
     * similar looking route.
     */
    public function testGetUser()
    {
        $resp = $this->getJson('/api/user/' . $this->unauthedUser->id);
        $resp->assertStatus(Response::HTTP_OK);
        $resp->assertJson(fn (AssertableJson $json) =>
            $json->where('id', $this->unauthedUser->id)
                 ->where('name', $this->unauthedUser->name)
                 ->where('email', $this->unauthedUser->email)
                 ->etc()
        );
    }
}
