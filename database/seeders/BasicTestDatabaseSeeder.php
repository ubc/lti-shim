<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\CourseContext;
use App\Models\Deployment;
use App\Models\EncryptionKey;
use App\Models\LtiRealUser;
use App\Models\LtiSession;
use App\Models\Platform;
use App\Models\PlatformClient;
use App\Models\Tool;

class BasicTestDatabaseSeeder extends Seeder
{
    /**
     * Seed the database with a basic setup to run our test cases on.
     *
     * @return void
     */
    public function run()
    {
        // make sure shim has an encryption key
        $encryptionKey = EncryptionKey::factory()->create();
        // shim's platform side config
        $shimPlatform = Platform::factory()->create([
            'name' => 'LTI Shim Platform Side',
            'iss' => config('lti.iss'),
            'auth_req_url' => route('lti.launch.auth'),
            'jwks_url' => route('lti.jwks.platform'),
            'access_token_url' => route('lti.token')
        ]);
        // shim's tool side config
        $shimTool = Tool::factory()->create([
            'name' => 'LTI Shim Tool Side',
            'client_id' => config('lti.own_tool_client_id'),
            'oidc_login_url' => route('lti.launch.login',
                                      ['toolId' => 'toolId']),
            'auth_resp_url' => route('lti.launch.redirect'),
            'target_link_uri' => route('lti.launch.midway'),
            'jwks_url' => route('lti.jwks.tool')
        ]);
        // create 2 platforms
        $platform1 = Platform::factory()->create();
        $platform2 = Platform::factory()->create();
        // create 2 tools
        $tool1 = Tool::factory()->create();
        $tool2 = Tool::factory()->create();
        // configure platform1 to be connectable to tool1
        $platformClient1 = PlatformClient::factory()->create([
            'platform_id' => $platform1->id,
            'tool_id' => $tool1->id
        ]);
        // create a deployment in platform1
        $deployment1 = Deployment::factory()->create([
            'platform_id' => $platform1->id
        ]);
        // create 2 courses in deployment1 and tool1
        $course1 = CourseContext::factory()->create([
            'deployment_id' => $deployment1->id,
            'tool_id' => $tool1->id
        ]);
        $course2 = CourseContext::factory()->create([
            'deployment_id' => $deployment1->id,
            'tool_id' => $tool1->id
        ]);
        // create 2 real users in platform1
        $realUser1 = LtiRealUser::factory()->create([
            'name' => 'One Instructor01',
            'platform_id' => $platform1->id
        ]);
        $realUser2 = LtiRealUser::factory()->create([
            'name' => 'A Student01',
            'platform_id' => $platform1->id
        ]);
        // create a LtiSession
        $ltiSession1 = LtiSession::factory()->create([
            'deployment_id' => $deployment1->id,
            'course_context_id' => $course1->id,
            'tool_id' => $tool1->id,
            'lti_real_user_id' => $realUser1->id,
            'platform_client_id' => $platformClient1->id
        ]);
    }
}
