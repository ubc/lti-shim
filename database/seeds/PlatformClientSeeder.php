<?php

use Illuminate\Database\Seeder;

class PlatformClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // insert ltijs tool client_id on reference implementation platform
        DB::table('platform_clients')->insert([
            'platform_id' => 2,
            'tool_id' => 2,
            'client_id' => 'StrawberryCat'
        ]);

        // insert ltijs tool client_id on test canavas
        DB::table('platform_clients')->insert([
            'platform_id' => 3,
            'tool_id' => 2,
            'client_id' => '112240000000000113'
        ]);
    }
}
