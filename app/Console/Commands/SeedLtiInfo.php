<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Jose\Component\KeyManagement\JWKFactory;

use App\Models\EncryptionKey;
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
        $this->seedPlatform();
        $this->seedTool();
        $this->seedEncryptionKey();
    }

    private function seedPlatform()
    {
        $platform = Platform::getOwnPlatform();
        if ($platform) return; // we only want to seed empty databases
        $platform = new Platform;
        $platform->name = 'LTI Shim Platform Side';
        $platform->iss = config('lti.iss');
        $platform->auth_req_url = route('lti.launch.platform.authReq');
        $platform->jwks_url = route('lti.jwks.platform');
        $platform->access_token_url = route('lti.token');
        $platform->save();
        // generate a new key
        $kid = date('c');
        $platformKey = new PlatformKey;
        $platformKey->kid = $kid;
        $platformKey->key = $this->generateKeyAsJson($kid);
        $platform->keys()->save($platformKey);
    }

    private function seedTool()
    {
        $tool = Tool::getOwnTool();
        if ($tool) return; // we only want to seed empty databases
        $tool = new Tool;
        $tool->name = 'LTI Shim Tool Side';
        $tool->client_id = 'Not used for shim, look up in platform_client';
        $tool->oidc_login_url = route('lti.launch.tool.login');
        $tool->auth_resp_url = route('lti.launch.tool.authResp');
        $tool->target_link_uri = route('lti.launch.platform.login');
        $tool->jwks_url = route('lti.jwks.tool');
        $tool->save();
        // generate a new key
        $kid = date('c');
        $toolKey = new ToolKey;
        $toolKey->kid = $kid;
        $toolKey->key = $this->generateKeyAsJson($kid);
        $tool->keys()->save($toolKey);
    }

    private function seedEncryptionKey()
    {
        $numKeys = EncryptionKey::count();
        if ($numKeys >= 1) return; // only need to seed if table empty
        $encryptionKey = new EncryptionKey;
        $key = JWKFactory::createRSAKey(
            4096,
            [
                'alg' => 'RSA-OAEP-256', // JWE compatible RSA-OAEP-256
                'use' => 'enc'   // used for encryption/decryption operations only
            ]
        );
        $encryptionKey->key = json_encode($key);
        $encryptionKey->save();
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
