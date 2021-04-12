<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database for development use.
     *
     * @return void
     */
    public function run()
    {
        $this->call(PlatformSeeder::class);
        $this->call(ToolSeeder::class);
        $this->call(PlatformClientSeeder::class);
        $this->call(EncryptionKeySeeder::class);
        $this->call(UserSeeder::class);
    }
}
