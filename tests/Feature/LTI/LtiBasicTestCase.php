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

    // TODO: stop caling seed() explicitly in setUp().
    //
    // There's two ways you can do database seeding in test cases. You can call
    // the seed() explicitly in the test case's setUp() or you can set the
    // protected $seed and (optionally) $seeder vars. It seems that you cannot
    // use the latter method if there are test cases using the first method.

    // Data changes during every test method's execution is rolled back using
    // transactions. When calling seed() explicitly in setUp(), this seeded
    // data is inside the transaction that gets rolled back. When using $seed,
    // this seeded data seems to be outside of the transaction and does NOT get
    // rolled back. This means that the next test class to get executed sees
    // this leftover seed data and complains about conflicts (if it is using
    // seed()).

    // I think this is a deliberate choice for performance reasons, as
    // calling seed() manually in setUp() means the seeder is run for every
    // test method, while using $seed means the seeder only needs to run once
    // for every test class.

    // This probably means that we have to uniformly use one or the other for
    // seeding. We're currently explicitly calling seed() in setUp(). We
    // probably should change over to using $seed if possible.
    //
    // don't run the default database seeder, use the seeder built for tests
    //protected $seeder = BasicTestDatabaseSeeder::class;
    // run the database seeder after database reset
    //protected $seed = true;

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
        // TODO: remove this in favor of using $seed for speedup
        $this->seed(BasicTestDatabaseSeeder::class);

        $this->platformClient = PlatformClient::first();
        $this->platform = $this->platformClient->platform;
        $this->tool = $this->platformClient->tool;
    }
}
