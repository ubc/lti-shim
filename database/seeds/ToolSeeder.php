<?php

use Illuminate\Database\Seeder;

class ToolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // insert the lti 1.3 php example tool
        DB::table('tools')->insert([
            'id' => 1,
            'name' => 'LTI 1.3 PHP Example Tool',
            'client_id' => 'StrawberryCat',
            'target_link_uri' => 'http://localhost:9001/game.php',
        ]);
        // make sure the tool has a deployment
        DB::table('deployments')->insert([
            'deployment_id' => '1',
            'tool_id' => 1,
            'platform_id' => 1
        ]);
        //
    }
}
