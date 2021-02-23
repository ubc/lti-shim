<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $ltijsTool = DB::table('tools')->where('name', 'Ltijs Demo Server')
                                       ->first();
        $riPlatform = DB::table('platforms')
            ->where('iss', 'https://lti-ri.imsglobal.org')->first();
        // insert ltijs tool client_id on reference implementation platform
        DB::table('platform_clients')->insert([
            'platform_id' => $riPlatform->id,
            'tool_id' => $ltijsTool->id,
            'client_id' => 'StrawberryCat'
        ]);

        $testCanvasPlatform = DB::table('platforms')
            ->where('iss', 'https://canvas.test.instructure.com')->first();
        // insert ltijs tool client_id on test canavas
        DB::table('platform_clients')->insert([
            'platform_id' => $testCanvasPlatform->id,
            'tool_id' => $ltijsTool->id,
            'client_id' => '112240000000000113'
        ]);
    }
}
