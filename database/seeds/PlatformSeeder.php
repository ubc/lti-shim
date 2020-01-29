<?php

use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // insert the reference implementation platform
        DB::table('platforms')->insert([
            'id' => 1,
            'name' => 'Reference Implementation',
            'iss' => 'https://lti-ri.imsglobal.org',
            'auth_req_url' => 'https://lti-ri.imsglobal.org/platforms/643/authorizations/new',
        ]);
        // make sure each platform has 1 client_id
        DB::table('platform_clients')->insert([
            'platform_id' => 1,
            'client_id' => 'StrawberryCat'
        ]);
    }
}
