<?php

namespace App\Console\Commands;

use Faker\Factory as Faker;

use Illuminate\Console\Command;

use Jose\Component\KeyManagement\JWKFactory;

use App\Models\Platform;
use App\Models\PlatformKey;
use App\Models\Tool;
use App\Models\ToolKey;

class SeedLtiInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lti:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Seed the database with the shim's own LTI platform and client info.";

    private $faker;
    
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Seed the database with the shim's own LTI Platform/Tool info.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->faker = Faker::create();
        $this->seedPlatform();
        $this->seedTool();
    }

    private function seedPlatform()
    {
        $platform = Platform::find(config('lti.own_platform_id'));
        if ($platform) return; // we only want to seed empty databases
        $platform = new Platform;
        $platform->name = 'LTI Shim Platform Side'; 
        $platform->iss = config('lti.iss');
        $platform->auth_req_url = config('app.url') .
            config('lti.platform_launch_auth_req_path');
        $platform->jwks_url = config('app.url') .
            config('lti.platform_jwks_path');
        $platform->save();
        // correct the id if needed
        if ($platform->id != config('lti.own_platform_id')) {
            $platform->id = config('lti.own_platform_id');
            $platform->save();
        }
        // generate a new key
        $kid = date('Y-m-d') . ' ' . $this->faker->unique()->colorName;
        $platformKey = new PlatformKey;
        $platformKey->kid = $kid;
        $platformKey->key = $this->generateKeyAsJson($kid);
        $platform->keys()->save($platformKey);
    }

    private function seedTool()
    {
        $tool = Tool::find(config('lti.own_tool_id'));
        if ($tool) return; // we only want to seed empty databases
        $tool = new Tool;
        $tool->name = 'LTI Shim Tool Side'; 
        $tool->client_id = 'Not used for shim, look up in platform_client';
        $tool->oidc_login_url = config('app.url') .
            config('lti.tool_launch_login_path');
        $tool->auth_resp_url = config('app.url') .
            config('lti.tool_launch_auth_resp_path');
        $tool->target_link_uri = config('app.url') .
            config('lti.platform_launch_login_path');
        $tool->jwks_url = config('app.url') .  config('lti.tool_jwks_path');
        $tool->save();
        // correct the id if needed
        if ($tool->id != config('lti.own_tool_id')) {
            $tool->id = config('lti.own_tool_id');
            $tool->save();
        }
        // generate a new key
        $kid = date('Y-m-d') . ' ' . $this->faker->unique()->colorName;
        $toolKey = new ToolKey;
        $toolKey->kid = $kid;
        $toolKey->key = $this->generateKeyAsJson($kid);
        $tool->keys()->save($toolKey);
    }

    private function generateKeyAsJson(string $kid): string {
        $key = JWKFactory::createRSAKey(
            4096, // Size in bits of the key. We recommend at least 2048 bits.
            [
                'kid' => $kid,
                'alg' => 'RS256',
                'use' => 'sig',
                'key_ops' => ['sign', 'verify'],
                'kty' => 'RSA'
            ]);
        return json_encode($key);
    }
}
