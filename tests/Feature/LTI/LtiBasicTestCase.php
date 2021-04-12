<?php

namespace Tests\Feature\LTI;

use Illuminate\Foundation\Testing\RefreshDatabase;

use Tests\TestCase;

use Database\Seeders\BasicTestDatabaseSeeder;

use App\Models\Platform;
use App\Models\PlatformClient;
use App\Models\Tool;

abstract class LtiBasicTestCase extends TestCase
{
    // will reset the database after each test
    use RefreshDatabase;

    // run the database seeder after database reset
    protected $seed = true;
    // don't run the default database seeder, use the seeder built for tests
    protected $seeder = BasicTestDatabaseSeeder::class;

    // commonly used models we need to setup tests
    protected Platform $platform; // lti launch's originating lti platform
    protected PlatformClient $platformClient; // lti platform/tool pair
    protected Tool $tool; // lti launch's target/destination lti tool

    /**
     * Retrieve commonly used models from the seeded database.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->platformClient = PlatformClient::first();
        $this->platform = $this->platformClient->platform;
        $this->tool = $this->platformClient->tool;
    }
}
