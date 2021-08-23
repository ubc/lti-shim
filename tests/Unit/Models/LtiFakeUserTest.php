<?php

namespace Tests\Unit\Models;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use Ramsey\Uuid\Uuid;

use App\Models\CourseContext;
use App\Models\Deployment;
use App\Models\LtiFakeUser;
use App\Models\LtiRealUser;
use App\Models\Platform;
use App\Models\PlatformClient;
use App\Models\Tool;

class LtiFakeUserTest extends TestCase
{
    use RefreshDatabase;

    private CourseContext $course;
    private LtiRealUser $realUser;
    private Platform $platform;
    private Tool $tool;

    protected function setUp(): void
    {
        parent::setUp();
        // we need to create some entries to satisfy table relations first
        $this->platform = Platform::factory()->create();
        $this->tool = Tool::factory()->create();
        $deployment = Deployment::factory()->create([
            'platform_id' => $this->platform->id
        ]);
        $this->course = CourseContext::factory()->create([
            'deployment_id' => $deployment->id,
            'tool_id' => $this->tool->id
        ]);

        $this->realUser = LtiRealUser::factory()->create([
            'platform_id' => $this->platform->id
        ]);
    }

    /**
     * Test creating one fake user.
     */
    public function testCreateFakeUser()
    {
        $this->assertEquals(0, $this->realUser->lti_fake_users()->count());

        $fakeUser = LtiFakeUser::getByRealUser($this->course->id,
            $this->tool->id, $this->realUser);

        $this->assertEquals(1, $this->realUser->lti_fake_users()->count());
        $this->checkFakeUser($this->realUser, $fakeUser);
    }

    /**
     * Test creating many fake users.
     */
    public function testCreateManyFakeUsers()
    {
        // create a ton of real users so we can generate a fake user for all of
        // them. Note that while we could use bulk create factory(), it turned
        // out to be slower and had weird issues, so we're using a for loop.
        $numRealUsers = 10000;
        $realUsers = [];
        for ($i = 0; $i < $numRealUsers; $i++) {
            $realUser = LtiRealUser::factory()->create([
                'platform_id' => $this->platform->id ]);
            $this->assertEquals(0, $realUser->lti_fake_users()->count());
        }
        // sanity check to make sure we've actually created that many users,
        // note we've already created one in setUp()
        $this->assertEquals($numRealUsers + 1, LtiRealUser::count());
        // these needs to be in a laravel Collection
        $realUsers = collect($realUsers);
        $fakeUsers = LtiFakeUser::getByRealUsers($this->course->id,
            $this->tool->id, $realUsers);
        // make sure each real user now has a fake user
        foreach ($realUsers as $realUser) {
            $this->assertEquals(1, $realUser->lti_fake_users()->count());
            $fakeUser = $realUser->lti_fake_users()->first();
            $this->checkFakeUser($realUser, $fakeUser);
        }
        // we've already checked over the generated fake users, but since we
        // retrieved them from their real users, we didn't check if the
        // getByRealUsers() returned list of fake users is as expected, so
        // we'll check that over again.
        foreach ($fakeUsers as $fakeUser) {
            $this->checkFakeUser($fakeUser->lti_real_user, $fakeUser);
        }
    }

    /**
     * Check that the $fakeUser generated for the $realUser has been properly
     * populated.
     */
    public function checkFakeUser(LtiRealUser $realUser, LtiFakeUser $fakeUser)
    {
        $this->assertEquals($this->course->id, $fakeUser->course_context_id);
        $this->assertEquals($realUser->id, $fakeUser->lti_real_user_id);
        $this->assertEquals($this->tool->id, $fakeUser->tool_id);

        // make sure we can reconstruct email
        $expectedEmail = Uuid::uuid5($fakeUser->sub, $fakeUser->name)
                             ->toString() . '@example.com';
        $this->assertEquals($expectedEmail, $fakeUser->email);
    }

    /**
     * The fake email needs to be unique or we'll end up forwarding them to the
     * wrong people.
     */
    public function testCannotCreateFakeUserWithDuplicateEmail()
    {
        $dupEmail = 'duplicate_email@example.com';
        $realUser2 = LtiRealUser::factory()->create([
            'platform_id' => $this->platform->id
        ]);
        $fakeUser1 = LtiFakeUser::factory()->create([
            'lti_real_user_id' => $this->realUser->id,
            'course_context_id' => $this->course->id,
            'tool_id' => $this->tool->id,
            'email' => $dupEmail
        ]);
        try {
            $fakeUser2 = LtiFakeUser::factory()->create([
                'lti_real_user_id' => $realUser2->id,
                'course_context_id' => $this->course->id,
                'tool_id' => $this->tool->id,
                'email' => $dupEmail
            ]);
        } catch (\Exception $e) {
            $this->assertInstanceOf(QueryException::class, $e);
            return;
        }
    }
}
