<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PassportCustomInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:custominstall';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'The default passport:install command creates extra client tokens. This version checks if client tokens has already been generated and will avoid creating extras.';

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $hasPersonalClient = DB::table('oauth_clients')
            ->where('personal_access_client', true)
            ->where('revoked', '!=', true)
            ->exists();
        $hasPasswordClient = DB::table('oauth_clients')
            ->where('password_client', true)
            ->where('revoked', '!=', true)
            ->exists();
        if ($hasPersonalClient && $hasPasswordClient) {
            // only need to see if we need to generate encryption keys 
            $this->call('passport:keys');
        }
        else {
            // can call the original install since no existing client
            $this->call('passport:install');
        }
    }
}
